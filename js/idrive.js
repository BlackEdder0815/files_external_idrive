$(document).ready(function() {


	OCA.External.Settings.mountConfig.whenSelectBackend(function($tr, backend, onCompletion) {
		if (backend === 'acd') {
			var backendEl = $tr.find('.backend');
			/*var el = $(document.createElement('a'))
				.attr('target', '_blank')
				.attr('title', t('files_external_idrive', 'Amazon Cloud Refresh'))
				.addClass('icon-settings svg')
			;
			el.click(function(){
				$.ajax({url: OC.generateUrl('apps/files_external_acd/refresh.php'),
				dataType: 'json',
				success: function(result){
						alert("update successful!");
					}
				});
			})
			el.tooltip({placement: 'top'});
			backendEl.append(el);*/
			
			/*
			var backendEl = $tr.find('.authentication');
			var el = $(document.createElement('a'))
			.attr('href', 'https://data-mind-687.appspot.com/clouddrive')
			.attr('target', '_blank')
			.attr('title', t('files_external_acd', 'Amazon Cloud initialization'))
			.addClass('icon-settings svg')
			;
			el.on('click', function(event) {
				var a = $(event.target);
				a.attr('href', generateUrl($(this).closest('tr')));
			});
			el.tooltip({placement: 'top'});
			backendEl.append(el);
			*/

		}
	});

});
