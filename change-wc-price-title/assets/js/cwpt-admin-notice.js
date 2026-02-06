jQuery(function ($) {
	$(document).on('click', '#cwpt-migration-notice .notice-dismiss', function () {
		$.ajax({
			url: cwptNotice.ajaxUrl,
			type: 'POST',
			data: {
				action: 'cwpt_dismiss_migration_notice',
				nonce: cwptNotice.nonce
			}
		});
	});
});