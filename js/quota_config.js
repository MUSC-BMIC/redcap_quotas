$(document).ready(function () {
    var $modal = $('#external-modules-configure-modal');

    //Show/Hide combined data dictionary file
    if(both_enabled)
       $(this).find('#external_modules_panel .x-panel-body .menubox .menubox').find('div')[2].style.display = 'block';
    else
       $(this).find('#external_modules_panel .x-panel-body .menubox .menubox').find('div')[1].style.display = 'none';

    $modal.on('DOMSubtreeModified', function (e) {

        // Making sure we are overriding this modules's modal only.
        if ($(this).data('module') !== quotaConfigSettings.modulePrefix) {
            return;
        }

        var $target = $(e.target);
        if ($target.is('tr.sub_start.sub_parent')) {
            $target.find(".external-modules-add-instance").text('Add Quota');
            $target.find(".external-modules-remove-instance").text('Remove Quota');
        } else if ($target.is('tr.sub_start.sub_child')) {
            $target.find(".external-modules-add-instance").text('Add Nested Quota');
            $target.find(".external-modules-remove-instance").text('Remove Nested Quota');
        }
    });

    $modal.on('show.bs.modal', function () {
        // Making sure we are overriding this modules's modal only.
        if ($(this).data('module') !== quotaConfigSettings.modulePrefix) {
            return;
        }

        $(document).ajaxComplete(function () {
            $modal.find("select[name*='field_name']").each(function () {
                quotaConfigSettings.useOldVal = "true"
                $(this).trigger('change');
                quotaConfigSettings.useOldVal = "false"
                cleanupFieldNameSelect();
            });

            $modal.find("select").each(function () {
                $(this).attr('data-live-search', true);
                $(this).selectpicker();
            });
        });

        /* Need to clear out the placeholder value that's assigned in the
         * 'rendered.bs.select hidden.bs.select' event handler so that the
         * user can actually use the search box for typeahead search.
         */
        $(document).on('shown.bs.select', function (e) {
            $(e.target).parent().find('input[type=search]').val('');
        });

        /* Need to assign this value to the search input whenever the bootstrap
         * select is closed because the general validation on all tds with class
         * 'requiredm' looks for a value for all interior inputs. By duplicating
         * the selected value in this input we can avoid unintentionally triggering
         * validation just because the typeahead search input is empty.
         */
        $(document).on('rendered.bs.select hidden.bs.select', function (e) {
            var $target = $(e.target);
            $target.parent().find('input[type=search]').val($target.val());
        });

        $(document).on('change', "select[name*='field_name']", function () {
            selectedVal = $(this).val();
            if (quotaConfigFields.hasOwnProperty(selectedVal)) {
                inputTd = $(this).closest("tr").next("tr").find("td.external-modules-input-td")
                oldInput = inputTd.find("input, select, textarea");

                // dropdowns and radio buttons
                if (['dropdown', 'radio'].indexOf(quotaConfigFields[selectedVal].field_type) != -1) {
                    options = quotaConfigFields[selectedVal].select_choices_or_calculations.split("|");
                    newSelect = '<select class="' + oldInput.attr('class') + '" name="' + oldInput.attr('name') + '">';

                    $.each(options, function (index, value) {
                        option = value.trim().split(", ");

                        if (quotaConfigSettings.useOldVal == 'true' && oldInput.val() == option[0]) {
                            newSelect += '<option value=' + option[0] + ' selected=selected>' + option[1] + '</option>';
                        }
                        else {
                            newSelect += '<option value=' + option[0] + '>' + option[1] + '</option>';
                        }

                    });

                    newSelect += '</select>';

                    if (oldInput.is('select')) {
                        oldInput.selectpicker('destroy');
                    }

                    oldInput.replaceWith(newSelect);

                    $modal.find("select").each(function () {
                        $(this).attr('data-live-search', true);
                        $(this).selectpicker();
                    });
                }

                // text, notes, and calculated fields
                if (['calc', 'text', 'notes'].indexOf(quotaConfigFields[selectedVal].field_type) != -1) {

                    if (quotaConfigSettings.useOldVal == 'true') {
                        newInput = '<input type="text" class="' + oldInput.attr('class') + '" name="' + oldInput.attr('name') + '" value="' + oldInput.val() + '">';
                    }
                    else {
                        newInput = '<input type="text" class="' + oldInput.attr('class') + '" name="' + oldInput.attr('name') + '">';
                    }

                    if (oldInput.is('select')) {
                        oldInput.selectpicker('destroy');
                    }

                    oldInput.replaceWith(newInput);
                }
            }
        });
    });
});

function cleanupFieldNameSelect() {
    // clean up the dropdown so that only fields that should be used for quotas are shown
    selector = "select[name*='field_name'] option"
    $.each($(selector), function () {
        if (quotaConfigValidFieldNameOptions.indexOf($(this).val()) == -1) {
            $(this).remove();
        }
    });

    setTimeout(cleanupFieldNameSelect, 100);
}

