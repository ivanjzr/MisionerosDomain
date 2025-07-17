define(function(){
    function moduleReady(modal, section_data){
        console.log("------select seat: ", section_data);






        //
        var total_amount = $("#total_amount").val();
        $("#total_amount2").val(total_amount);



        



        function setCustomerData(data){
            //
            $(".customer_id")
                .val(data.id)
                .text(data.tipo + " ID: " + data.id);
            //
            $("#customer_name").val(data.name);
            $("#email").val(data.email);
            $("#phone_country_id").val(data.phone_country_id);
            $("#phone_number").val(data.phone_number);
            //
            if ( parseInt(data.customer_type_id) === 2 && data.allow_credit ){
                $("#a_credito_container").show();
            } else {
                $("#a_credito_container").hide();
            }
        }


        function resetCustomerData(){
            //
            $("#a_credito_container").hide();
            //
            $("#customer_id").empty();
            $("#customer_id").trigger("change");
            //
            $(".customer_id")
                .val("")
                .text("");
            $("#customer_name").val("");
            $("#email").val("");
            $("#phone_country_id").val("");
            $("#phone_number").val("");
        }



        //
        $.S2Ext({
            S2ContainerId: "customer_id",
            placeholder: "...buscar cliente",
            //language: "es",
            language:{
                noResults:function(){return""},
                searching:function(){return""}
            },
            allowClear: true,
            minimumInputLength: 2,
            minimumResultsForSearch: "-1",
            remote: {
                qs: function(){
                    return {
                        name: "tc",
                        val: $("input[name=tipo_cliente]:checked").val(),
                    }
                },
                url: app.admin_url + "/customers/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: function(item){
                    if (item.loading) {
                        return item.text;
                    }
                    var str_email = item.email ? item.email + " - " : "";
                    var str_company_name = (item.id && item.company_name) ? item.company_name + " - " : "";
                    return str_email + str_company_name + item.name;
                },
                templateSelection: function(item){
                    if (item.id){
                        var str_email = item.email ? item.email + " - " : "";
                        var str_company_name = (item.id && item.company_name) ? item.company_name + " - " : "";
                        return str_email + str_company_name + item.name;
                    }
                    return item.text;
                }
            },
            onChanged: function(sel_id, data){
                //
                console.log(sel_id, data);
                //
                if (data && data.id){
                    setCustomerData(data);                    
                } else {
                    resetCustomerData();  
                }
            },
            onClose: function(){
                //
                var customer_id = $("#customer_id").val();
                console.log(customer_id);
                //
                if (!customer_id){
                    resetCustomerData();  
                }
            }
        });




        



        $.blockUI.defaults.baseZ = 99999;



        function showHidePagoTarjeta(metodo_pago){
            //console.log("***metodo_pago: ", metodo_pago);
            $("#square_card_container").hide();
            //
            if (metodo_pago===app.SQR_CARD){
                $("#square_card_container").show();
            } 
        }



        
        //
        $("input[name=metodo_pago]").click(function(e) {
            //
            showHidePagoTarjeta($(this).val());
        });





        


        //
        $("input[name=tipo_cliente]").click(function(e) {
            
            //
            resetCustomerData();
            //
            var tipo_cliente_val = $("input[name=tipo_cliente]:checked").val();
            //console.log(tipo_cliente_val);
            
            //
            $("#search_customer_container").hide();
            $("#efectivo_container").show();
            //block("#search_customer_container", null); 
            $("#search_type_title").text("Buscar Cliente");
            
            
            //
            if (parseInt(tipo_cliente_val)===0){

                //
                $("#customer_name").focus();
                

            } else if ( (parseInt(tipo_cliente_val)===1) || (parseInt(tipo_cliente_val)===2) ){                
            
                //
                $("#search_customer_container").show();
                //$("#search_customer_container").unblock(); 

                //
                if ( parseInt(tipo_cliente_val)===2 ){
                    $("#efectivo_container").hide();
                    $("#search_type_title").text("Buscar Vendedor");
                }

            }

        });




         //
         loadSelectAjax({
            id: "#phone_country_id",
            url: app.public_url + "/paises/list",
            parseFields: function(item){
                return "+" + item.phone_cc + " (" + item.abreviado + ")";
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: ((section_data && section_data.phone_country_id) ? section_data.phone_country_id : false),
            enable: true
        });


        



       
        






        /*--------------------------------- SQUARE SECTION ---------------------------------------*/
		


         //
         $('#form_send').validate();


		//
		function postSale(square_data){
            

            //
            var cant_expired_items = vent.getElemsExpired();
            if (cant_expired_items){
                app.Toast.fire({icon: 'warning', title: "Tienes " + cant_expired_items + " elementos expirados, actualizalos para continuar"});
                //
                enable_btns();
                return;
            }

            //
            var the_arr = getLSItem("arr_sale_items");
            //
            var post_obj = {
                items: the_arr,
                customer_id: $("#customer_id").val(),
                tipo_cliente: $("input[name=tipo_cliente]:checked").val(),
                customer_name: $("#customer_name").val(),
                email: $("#email").val(),
                phone_country_id: $("#phone_country_id").val(),
                phone_number: $("#phone_number").val(),
            }


             /*
            //
            var post_obj = {};
			//
			if (app.auth_user){
				//post_obj.url = app.api_url + "/customer/request-catering";
				post_obj.url = app.public_url + "/request-catering";
				post_obj.authToken = app.auth_user.token
			} else {
				post_obj.url = app.public_url + "/request-catering";
			}
			
			//
			
            */

            var new_post_data = $.extend(post_obj, square_data);

            //
            $.ajax({
                type:'POST',
                url: app.admin_url + "/ventas/add",
                dataType: "json",
                data: JSON.stringify(new_post_data),
                beforeSend: function( xhr ) {
                    //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );                    
                },
                contentType: "application/json",
                success:function(response){
                    //console.log(response); return;
                    //
                    enable_btns();

                    // 
                    if (response && response.id){
                        
                        // 
                        localStorage.removeItem("arr_sale_items");
                            //
                            vent.clearCounters();
                            vent.updateShowBtn();
                            //
                            $("#modal-checkout").find('.modal').modal("hide");
                            $("#modal-show-sale").find('.modal').modal("hide");
                            $("#grid_section").DataTable().ajax.reload();

                            //
                        app.temp_sale_id = vent.generateTempSaleId();
                        console.log("new temp_sale_id 2: ", app.temp_sale_id);
                    }
                    //
                    else if (response.error){
                        app.Toast.fire({ icon: 'error', title: response.error});
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }

                },
                error: function(){
                    enable_btns();
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });

                }
            });

		}


       

     
    



        let cardObj;
    

		//
		if ( typeof square_app_id != 'undefined' && square_app_id){


			// This function tokenizes a payment method.
			// The ‘error’ thrown from this async function denotes a failed tokenization,
			// which is due to buyer error (such as an expired card). It is up to the
			// developer to handle the error and provide the buyer the chance to fix
			// their mistakes.
			async function tokenize(paymentMethod) {
				const tokenResult = await paymentMethod.tokenize();
				if (tokenResult.status === 'OK') {
					return tokenResult.token;
				} else {
					let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
					if (tokenResult.errors) {
						errorMessage += ` and errors: ${JSON.stringify(
							tokenResult.errors
						)}`;
					}
					throw new Error(errorMessage);
				}
			}

			// Helper method for displaying the Payment Status on the screen.
			// status is either SUCCESS or FAILURE;
			function displayPaymentResults(status) {
				const statusContainer = document.getElementById(
					'payment-status-container'
				);
				if (status === 'SUCCESS') {
					statusContainer.classList.remove('is-failure');
					statusContainer.classList.add('is-success');
				} else {
					statusContainer.classList.remove('is-success');
					statusContainer.classList.add('is-failure');
				}

				statusContainer.style.visibility = 'visible';
			}




			async function initializeCard(payments) {
				const card = await payments.card();
				await card.attach('#card-container');
				return card;
			}



			
			async function loadSquareContent(){

				//
				if (!window.Square) {
					throw new Error('Square.js failed to load properly');
				}


				//
				const payments = window.Square.payments(square_app_id, square_loc_id);
                //				
				try {
					cardObj = await initializeCard(payments);
				} catch (e) {
					console.error('Initializing Card failed', e);
					return;
				}



				app.handlePaymentMethodSubmission = async function (event, paymentMethod) {
					event.preventDefault();
					try {
                            
                        //
                        disable_btns();
                        

                        //
                        const token = await tokenize(paymentMethod);
                        displayPaymentResults('SUCCESS');
                        
                        //
                        postSale({
                            type: app.SQR_CARD,
                            token_id: token,
                            idempotency_key: SqrGenUUID(),
                            location_id: square_loc_id,
                        });

					} catch (e) {
                        //
                        enable_btns();
                        //
						displayPaymentResults('FAILURE');
						console.error(e.message);
					}
				}

			}

			loadSquareContent();
		}
        /*--------------------------------- END SQUARE SECTION ---------------------------------------*/


        


            
        $("#form_send").submit(async function(evt){
            evt.preventDefault();
            //
            var metodo_pago = $("input[name=metodo_pago]:checked").val();
            //alert(metodo_pago);
            //
            if ( $('#form_send').valid() ) {


                //
                disable_btns();
                
                //
                if (metodo_pago===app.CASH){
                    // 
                    postSale({
                        type: app.CASH,
                    });
                } 
                //
                else if (metodo_pago===app.SQR_CARD){
                    // await 
                    await app.handlePaymentMethodSubmission(evt, cardObj);
                }
                //
                else if (metodo_pago===app.CREDIT){
                    // 
                    postSale({
                        type: app.CREDIT,
                    });
                } else {
                    //
                    enable_btns();
                    app.Toast.fire({icon: 'info', title: "metodo de pago no valido"});
                }
             
                
            }
        });
		
		

        // intial defaults
        $("#tc_ciente").attr("checked", true);
        $("#efectivo_container").show();




    }
    return {init: moduleReady}
});