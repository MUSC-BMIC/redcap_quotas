$(document).ready(function() {
	var counter = 0;
	$("#submit-btn-saverecord")[0].onclick = function(e) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		//Figure out if they meet their quotas here
		$(counter++ % 2 === 0
			? '#quota-success-modal'
			: '#quota-failure-modal').modal('show');
	};

	// $("#external-modules-disable-button-confirmed").click(function(e) {
	// 	dataEntrySubmit(this);
	// 	return false;
	// });
});