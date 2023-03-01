class fetch {
  static async Balance () {
	  try {
		  let response = await axios.post(urls.ajax, {
			  get_balance_resellers: 1,
		  });
		  return response.data.balance; 
	  } catch (error) {
		  console.log('error:', error);
		  return false;
	  }	  
  }
  static async Messages (order_id) {
	  try {
		  let response = await axios.post(urls.ajax, {
			  get_messages: 1,
			  order_id : order_id,
			  campo: 'messages'
		  });
		  //console.log(response,order_id);
		  return response.data.messages; 
	  } catch (error) {
		  console.log('error:', error);
		  return false;
	  }	  
  }
  static async Orders (filters) {
	  //console.log(filters, 'fetchOrders');
	  try {
		  let response = await axios.post(urls.ajax, {
				  get_orders_reseller: 1,
				  filters: filters  
		  });
		  //console.log(response, 'response-fetchOrders');
		  return response.data;

	  } catch (error) {
		  console.log('error:', error);
          return false;
	  }
  }
}
class html {
	static classCardInfo = '.card-info';
	static idCardBodyInfo = '#card-body-info';
	static idContentOrders = '#content-orders';
	static idFiltersInfo = '#filters-info';
	static idLoadingCard = '#loading-btn';
	static idLoadingCsv = '#loading-csv';
	static idLoadingOrders = '#loading-orders';
	static idMessagesModal = '#messagesModal';
	static idModals = '#container-extra';

    static button(valor, class_button, title, name, content) {
		return `<button 
		          type="button" 
		          class="${class_button}" 
		          title="${title}" 
		          attr-id="${valor}" 
		          name="${name}" >
			      ${content} 
			</button>`;
	}
	static buttonModal(valor, class_button, title, name, content, target) {
		return `<button 
		          type="button" 
		          class="${class_button}" 
		          title="${title}" 
		          name="${name}" 
		          attr-id="${valor}" 
                  data-toggle="modal"
			      data-target="${target}">
			      ${content} 
			</button>`;
	}
	static divBalance(balance) {
		return `<h5 class="text-center">${balance}$</h5>`;
	}

	static divAlert(text, classAlert = 'alert-danger') {
		return `<p class="alert ${classAlert}  m-0 p-2 m-1 text-center">${text}</p>`;
	}

	static divFilters(info, filters) {
		//console.log(resellers);
		//console.log(info, filters, 'total&filters');
		let divFilters = `<div class="h6"><span class="badge badge-info">Total: ${info.total}</span></div>`;
		divFilters += `<div class="h6"><span class="badge badge-info">Total Ventas: ${info.total_sales}</span></div>`;

		if (!filters.hay_filtros) {
			divFilters += `<div class="h6"><span class="badge badge-warning">Excluidos Errores y Cancelados</span></div>`;
		} else {
			divFilters += Object.keys(filters)
				.filter((key, value) => key != 'hay_filtros')
			    .map( (key,v) => {
					//console.log(key, filters[key]);
					let value = filters[key];
					if (key == 'order_state') {
						value = resellers.order_option_selected;
					}
				    return `<div class="h6"><span class="badge badge-secondary">${key.toUpperCase().replace('_', ' ')}: ${value.toUpperCase()}</span></div>`;	
				});
		}
		return divFilters;
	}
	static divModals(modals) {

		let html = '';
		let array_modals = modals.map( (modal) => {
			return `<div class="modal" id="${modal.id}" tabindex="-1" role="dialog" aria-labelledby="${modal.id}Label" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content bg-info ">
						<div class="modal-header p-1 text-light">
							<h5 class="modal-title pl-2" id="${modal.id}Label">${modal.title}</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body bg-light p-1">
							<div class="spinner-border m-0" role="status" id="loading-products">
								<span class="sr-only">Cargando ...</span>
							</div>
						</div>
						<div class="modal-footer p-1 bg-light">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
						</div>
					</div>
				</div>
			</div>`;
		});
		html = array_modals.join('');

		return html;
	}
	static divOrders(orders) {
		let campos;
		let cabeceras;
		if (orders.hasOwnProperty(0)) {
			let primeraFila = [orders[0]];
			cabeceras = primeraFila.map((item, index) => {
				let campos = Object.keys(item);
				let camposTh = campos
				    .filter((campo) => {
						return (campo != 'no-header' ? true : false);
					})
					.map((campo) => {
					    return [campo,`<th class="th_order_${campo}">${campo.toUpperCase().replace('_', ' ')}</th>`];
				    });
				return camposTh;
			})	
		}	
		
		let tabla = `<table class="table table-sm table-hover table-orders"><thead>` 
			+ cabeceras[0]
			.map((cab) => cab[1])
			.join('') 
			+ `</thead>`  
			+ orders.map((order) => {
				//console.log(order);
				let td_campos = `<tr>`;
				cabeceras.forEach((campo) => {
					campo.map((e,v) => {
						const [cabecera] = e;
						let valor = html.FormarOrderValues(cabecera, order[cabecera.toLowerCase()], order['no-header']);
						td_campos += `<td>${valor}</td>`;
					});
				});
				td_campos += `</tr>`;
				return td_campos;
		}).join('') + `</table>`;

		return tabla;
	}

	static divSpinnerLoading(text) {
		return `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
					 ${text}`;
	}

	static FormarOrderValues(campo, valor, extraOrder) {
		//console.log(extraOrder, 'extraOrder');
		let content = '';
		switch(campo) {
			case 'mensajes':
				if (extraOrder.cant_messages >= 1) {
					content = `Ver`;
					return html.buttonModal(
						valor, 
						'btn btn-sm btn-info verMensajes', 
						'Ver mensajes', 
						'see-mensajes', 
						content,
						html.idMessagesModal
					);
				} else {
					return '';
				}

				break;
			case 'productos':
				return valor.join('<br>');
				break;
			case 'estado':
				return html.infoEstado(valor, extraOrder.color);
				break;
			case 'pdf':
				return `<a href="index.php?pdf&id_order_pdf=${valor}" target="_blank">
					  <img src="../images/pdf.png" role="button" class="cursor" title="[Factura orden ${valor}]" attr-id="${valor}" attr-name='pdf-order' >
					</a>`;
				break;
			default:
				return valor;
		}
	}

	static infoEstado(valor, color) {

		let name = valor.toUpperCase();
		let bg = ( color !== null ? color : 'grey');
		let class_text = 'light';

		return `<div class="alert m-0 rounded-0 text-${class_text} text-center p-1" style="background-color:${bg}">${name}</div>`;
	}
}
const csv = {
	create: function (orders) {
		//console.log(orders);
		let csv = 'LocalReference;Referencia;Timestamp;Montos;Telefono;Producto\n';
		orders.map((order) => {
			['external_reference', 'id_order', 'fecha', 'monto', 'telefono','productos'].forEach( (cabecera) => {
				let campo = order[cabecera];
				//console.log(cabecera, order, campo);
				if (cabecera == 'productos') {
					campo = order[cabecera].join(',');
				}
				csv += `${campo};`;
			});
			csv += '\n';
		});
		
		const blob = new Blob([csv], { type: 'text/csv' });
		const url = window.URL.createObjectURL(blob)
		const a = document.createElement('a')
		a.setAttribute('href', url)
		a.setAttribute('download', 'ordenes_conciliacion.csv');
		a.click()
		return true;
	}
}
const modals = [
	{'id':'messagesModal', 'title' : 'Mensajes'}
];
const spinners = [
	{'text':'Cargando datos ...', 'elem': html.idLoadingCard},
	{'text':'Exportando ...', 'elem': html.idLoadingCsv},
	{'text':'Cargando ordenes ...', 'elem': html.idLoadingOrders}];

const urls ={
    ajax : './inc/ajax.php'
}
export const resellers = {

  order_option_selected :'',
  orders : '',	

  addListeners: () => document.addEventListener('click', resellers.clickHandler.bind(resellers)),

  clickHandler: function (event) {
	  console.clear();
	  const elementClicked = (event.target.name == '' ? event.target.getAttribute('attr-name') : event.target.name);
	  //console.log(elementClicked);	  
	  switch (elementClicked) {
          case 'filter':
	          //console.log('buscar ORdenes con filtros');
	          resellers.renderOrders();
      break;
          case 'export-orders':
			  resellers.exportOrders();
	      break;
		  case 'order_state':
			  resellers.setOrderStateSelected();
		  break;
		  case 'see-mensajes':
			  resellers.showMessages(event.target.getAttribute('attr-id'));
	      break;		  
	  }
  },
  exportOrders : function () {
	  resellers.showLoading(html.idLoadingCsv);
	  let myPromise = new Promise((solve,reject) => {
	  	let res = csv.create(resellers.orders);
	    solve(true);
	  });
      myPromise.then(
	    function () {
	      resellers.showLoading(html.idLoadingCsv, true);
		}
      );   	
  },
  getFilters: function () {
	  const form = document.getElementById('form-filter-orders');
	  const formData = new FormData(form);
	  //console.log(formData,'getFilters');
      const filterPost = {};
	  let hay_filtros = false;
	  for (const [key, value] of formData) {
		  if (value != '' && value != '-') {
			hay_filtros = true;
		  	filterPost[key] = value;
		  }
	  }
	  filterPost['hay_filtros'] = hay_filtros;
	  return filterPost;
  },
  loadDatetime: (datetimevals) => {
      if (window.jQuery().datetimepicker) {
		  datetimevals.forEach(e => {
              $(e).datetimepicker({
                  format: 'YYYY-MM-DD',
              });
		  });
      }	
  },
  init: function ( options = {} ) {
	  resellers.loadDatetime(options.datetimepicker);
      resellers.renderSpinners();
	  resellers.renderModals();
	  resellers.addListeners();	 
	  resellers.renderCardInfo();
	  resellers.renderOrders();
  },
  renderCardInfo: async function () {
	  resellers.showDiv(html.classCardInfo);
	  resellers.showLoading(html.idLoadingCard);
	  
	  setTimeout( () => {
		  if ($(html.idCardBodyInfo).is(':empty') !== false) {
			  $(html.idLoadingCard).hide('slow');
			  $(html.idCardBodyInfo).html(html.divAlert('No hay nada que mostrar', 'alert-info'));
			  setTimeout(function () {
                  $(html.classCardInfo).remove();
			  }, 4900);
		  }
	  }, 5000);

	  let balance = await fetch.Balance();
	  $(html.idLoadingCard).hide('slow');
	  if (balance !== false) {	  
	      $(html.idCardBodyInfo).removeClass('p-0');
		  $(html.idCardBodyInfo).addClass('p-2');
		  
	      $(html.classCardInfo + ' .card-header')
			  .removeClass('bg-white')
			  .addClass('p-2 text-center')
			  .html('<h6>Balance</h6>');

		  $(html.idCardBodyInfo).html(html.divBalance(balance));
	  } else {
	      resellers.showLoading(html.idLoadingCard, true);
          $(html.idCardBodyInfo).html(html.divAlert('Error buscando el balance', 'alert-danger'));
	  }
  },
  renderModals: function () {
		$(html.idModals).html(html.divModals(modals));
  },
  renderOrders: async function () {
	  
      const filterPost = resellers.getFilters();
      //console.log(filterPost, 'filterPost');	  
	  resellers.showLoading(html.idLoadingOrders);

	  let res = await fetch.Orders(filterPost);
      //console.log(res);
	  if (res !== false) {
		  if (res.hasOwnProperty('error')) {
			  if (res.error == 'no-session') {
				location = 'login.php';
			  }
		  }
          let total = res.info_labels?.total;
		  resellers.showLoading(html.idLoadingOrders, true);

		  if (total > 0) {	 
			   $(html.idFiltersInfo).html(html.divFilters(res.info_labels, res.filters));
			   $(html.idContentOrders).html(html.divOrders(res.orders));
 		       resellers.orders = res.orders;
		  } else {
			   $(html.idContentOrders).html(html.divAlert('No hay ordenes', 'alert-danger'));
		  }

	  } else {
	      resellers.showLoading(html.idLoadingOrders, true);
          $(html.idContentOrders).html(html.divAlert('Error buscando ordenes', 'alert-danger'));
	  }
  },
  renderSpinners: function () {
		$(spinners).each((i, spinner) => {
      	    $(spinner.elem).html(html.divSpinnerLoading(spinner.text));
		});
  },
  setOrderStateSelected: () => resellers.order_option_selected = $('select[name="order_state"]').find(":selected").text(),
  showDiv: (e) => {
	  $(e).show();
  },
  showLoading: (e, hide = false) => {
	  (!hide) ? $(e).show() : $(e).hide();;
  },
  showMessages: async (id) => {
	  //console.log('buscar mensajes de: ' + id);
	  let html_messages = '';
	  let messages = await fetch.Messages(id);
	  
	  if (parseInt(messages.length) >= 1) {
		  html_messages = `<table class="table m-0">
			  <thead><th>Fecha</th><th>Mensaje</th></thead>`;
		  messages.map((msg) => {
			  //console.log(msg);
              html_messages += `<tr><td>${msg.date_add}</td><td>${msg.message}</td></tr>`;
		  });
		  html_messages += `</table>`;
	  } else {
		  html_messages = `<p class="alert alert-info">No hay mensajes</p>`;
	  }
	  $(html.idMessagesModal + ' .modal-body').html(html_messages);
  }
}
