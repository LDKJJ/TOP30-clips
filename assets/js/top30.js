(function ($) {
	$(document).ready(function () {

		$('#top30-grid > tbody').sortable({
			stop: function(event, ui) {
				var cpt = 1
				$("table#top30-grid > tbody  > tr").each(function(i, tr) {
					$( tr ).find('.position-input').val( cpt )
					cpt++
				});
			}
		});

		$('#add-row-top30').click(function () {
			var rowCount = $('table#top30-grid tbody tr').length;
			var row = $( "table#top30-grid tbody tr:first-child" ).clone()

			$( row ).find('.content-search').remove()
			$( row ).find('td:eq(0) .search-panel').append('<div class="content-search"></div>')
			$( row ).find('td:eq(0) input').attr('id', '__track-' + rowCount)
			$( row ).find('td:eq(0) input.track-input').attr('value', 0)
			$( row ).find('td:eq(3)').html('')
			//$( row ).find('td:eq(1) input').attr('id', '__position-' + rowCount)
			$( row ).find('td:eq(1) input.position-input').attr('value', rowCount + 1)
			$( row ).find('td:eq(4) input.total_rate').attr('value', 0)
			$( row ).find('td:eq(4) span.top30_entries-total-votes').text(0)
			$( row ).find('td:eq(5)').html('<a href="javascript: void(0)" class="remove-row" data-id="row-'+ rowCount +'">Supprimer</a>')
			$('table#top30-grid tbody#form-rows').append('<tr id="row_' + rowCount + '">' + $(row).html() + '</tr>')
		})

		$(document).on("click", ".remove-row", function() {
			var cpt = 0

			$( this ).closest('table#top30-grid tbody tr').remove()
			$("table#top30-grid > tbody  > tr").each(function(i, tr) {
				$( tr ).attr('id', 'row-' + cpt)
				cpt++
			});
		});

		var is_clicked_tr = false

		$(document).on('click', 'table.clips tr',  function () {
			$(this).closest('td').find('input.track-input').val($(this).data('id'))
			is_clicked_tr = true
		})

		$(document).click(function(event) {
			if ( !$(event.target).hasClass('content-search')) {
				$(".content-search").hide();
			}
		})

		$(document).on('keyup', '.input-search-clips', function () {
			console.log($(this).val().length)
			if ($(this).val().length >= 3) {
				runSearch($(this), $(this).val())
			} else {
				$(this).closest('.search-panel').find('.content-search').html('').hide()
			}
		})

		runSearch = function(currentInput, key) {
			$.ajax({
				url: drupalSettings.path.baseUrl + "admin/top30/search-clips",
				type: "post",
				data: { key: key },
				dataType: "json",
				beforeSend: function() {
					$( currentInput ).closest('.search-panel').find('.content-search').addClass('auto-height')
					$( currentInput ).closest('.search-panel').find('.content-search').html('')
					$( currentInput ).closest('.search-panel').find('.content-search').append('<h5 style="text-align:center">Chargement...</h5>').show()
				},
				success: function (response) {
					if( response.count > 0 ) {
						$( currentInput ).closest('.search-panel').find('.content-search').removeClass('auto-height').show()
						$( currentInput ).closest('.search-panel').find('.content-search').html(response.result)
					} else {
						$( currentInput ).closest('.search-panel').find('.content-search').addClass('auto-height')
						$( currentInput ).closest('.search-panel').find('.content-search').html('')
						$( currentInput ).closest('.search-panel').find('.content-search').append("<h5>Pas de résultat!</h5>")
					}
				},
				complete: function (response) {
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.log(textStatus, errorThrown)
				}
			})
		}

		submitForm = function () {
			var form = $('#top30-form');
			var formData = form.serialize()
			var btn = document.querySelector('#top30-form input[type=submit]')
			var btn_role = btn.getAttribute('data-role')
			var token = ( $('#top30_token').length ? $('#top30_token').val() : '' )

			$.ajax({
				url: drupalSettings.path.baseUrl + "admin/top30/" + btn_role + "/" + token,
				type: "post",
				data: formData,
				dataType: "json",
				beforeSend: function() {
					btn.value = 'Chargement...'
				},
				success: function (response) {
					if (btn_role == 'edit') {
						var Listchecked = Array();
						$('input[type=checkbox]:checked').each(function () {
							Listchecked.push($(this))
						});

						for(i=0;i<Listchecked.length;i++) {
							Listchecked[i].closest('tr').remove()
						}
					}
					showMessage(response, btn_role)
				},
				complete: function (response) {
					btn.value = 'Valider'
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.log(textStatus, errorThrown)
				}
			})
		}

		showMessage = function (response, btn_role) {
			var messages = ''
			console.log(response)
			if (response.errors) {
				for (i in response.errors) {
					status = 'error'
					messages += response.errors[i].message + '</br>'
				}
			} else if (response.success) {
				status = 'status'
				messages = response.success.message

				if (btn_role != 'edit') {
					$('input, textarea').val('');
					$('#top30-grid input.track-input, #top30-grid input.position-input').val(0);	
					$('select').prop('selectedIndex', 0)
				}
			}
			if (document.getElementById('block-messages')) {
				document.getElementById('block-messages').remove()
			}
			block_message = '<div role="contentinfo" style="margin-bottom:10px; font-size: 13px" aria-label="'+ status +' message" class="messages messages--'+ status +'"><div role="alert"><h2 class="visually-hidden">'+ status +' message</h2>' + messages +'</div></div>'
			var form_element = document.getElementById('top30-form')
			var block = document.createElement('div')
			block.innerHTML = block_message
			block.setAttribute('id', 'block-messages')
			form_element.parentNode.insertBefore(block, form_element);
			var element = document.getElementById("block-messages");
			element.scrollIntoView({ behavior: 'smooth', block: 'center' })
		}

		insertAfter = function (newNode, referenceNode) {
			referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
		}
	})
})(jQuery)