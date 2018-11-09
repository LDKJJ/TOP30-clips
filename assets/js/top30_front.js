(function ($) {
	$(document).ready(function () {

		$('body').on('click', '.top30-grid .vote-this', function (e) {
			e.preventDefault();

			var btn = $( this )
			var nid = $(this).data('id')
			var token = $('.date-classement').data('token')

			$.ajax({
				url: drupalSettings.path.baseUrl + "/admin/top30/vote-clip",
				type: "GET",
				data: { nid: nid, token: token },
				beforeSend: function() {
					$( '.slow-alert' ).remove()
					btn.closest('.bg-hover').find('.b-vote').find('.fa-spin').remove()
					btn.closest('.bg-hover').find('.b-vote').append('<i class="fa fa-spinner fa-spin '+ $( btn ).data('spin-type') +'"></i>')
				},
				success: function (response) {
					var elt = document.createElement('div')
					elt.setAttribute('class', 'slow-alert')
					elt.setAttribute('id', 'danger')
					if (response.success) {
						elt.innerText = response.success.message
						elt.setAttribute('id', 'success')
						$( btn ).find('.heart').toggleClass("is-active")
						$( btn ).delay(1000)
						.queue(function() {
							$(this).html('<i class="fa fa-heart green-heart"></i>')
						});
					} else {
						elt.innerText = response.danger.message
						elt.setAttribute('id', 'danger')
					}
					$( 'body' ).append( elt )
					btn.closest('.bg-hover').find('.b-vote .fa-spinner').remove()
					$( elt ).fadeIn( 'slow' ).delay(3000).fadeOut("slow")
				},
				complete: function (response) {},
				error: function(jqXHR, textStatus, errorThrown) {
					console.log(textStatus, errorThrown)
				}
			})
		});

		fetchClips = function (obj, type_view) {

			$( '.top30-grid .head-btn a' ).removeClass('active')
			$( obj ).addClass('active')

			$.ajax({
				url: global_json.baseUrl + "/top30/get",
				type: "post",
				data: { type_view: type_view },
				dataType: "json",
				beforeSend: function() {
					$('.top30-grid .clips').css({ 'opacity': '.3' })
					$('.top30-grid .clips').append('<div class="loader"><i class="fa fa-spinner fa-spin" style="font-size:24px"></i></div>')
					$(".loader").show()
				},
				success: function (response) {
					if(!jQuery.isEmptyObject(response.result)) {
						$('.top30-grid .clips').html(response.result)
					} else {
						$('.top30-grid .clips').html("<h5 style='padding-left: 7px'>Pas de résultat!</h5>")
					}
				},
				complete: function (response) {
					if (type_view == 'list') {
						$('.top30-grid .clips').removeClass('fix-w-container')
					} else {
						$('.top30-grid .clips').addClass('fix-w-container')
					}
					$('.top30-grid .clips').css({ 'opacity': 'unset' })
					$('.top30-grid .clips .loader').remove()
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.log(textStatus, errorThrown)
				}
			})

		}
	})
})(jQuery)