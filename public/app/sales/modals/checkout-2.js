define(function(){
    function moduleReady(modal, sale_data){
        console.log("------sale_data: ", sale_data);






        //        
        $(".customer_id").text(" ID: " + sale_data.customer_id);
        $("#customer_name").text(sale_data.customer_name);
        $("#customer_email").text(sale_data.email);
        $("#customer_phone").text(sale_data.phone_number);
		$("#total_amount2").text(sale_data.grand_total);
        
        

        $.blockUI.defaults.baseZ = 99999;




        /*--------------------------------- SQUARE SECTION ---------------------------------------*/
		


         //
         $('#form_send').validate();


		//
		function postSale(square_data){
            

            		
			
            //cardButton.disabled = true;
			//console.log(new_post_data);

            //
            $.ajax({
                type:'POST',
                url: app.admin_url + "/ventas/" + sale_data.id + "/pay-square",
                dataType: "json",
                data: JSON.stringify(square_data),
                beforeSend: function( xhr ) {
                    //xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
                    disable_btns();
                    preload(true);
                },
                contentType: "application/json",
                success:function(response){
                    //console.log(response); return;
                    //
                    enable_btns();

                    // 
                    if (response && response.id){
                        
                        
                        //
                        vent.clearCounters();
                        vent.updateShowBtn();
                        //
                        $("#modal-checkout").find('.modal').modal("hide");
                        $("#grid_section").DataTable().ajax.reload();
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
				let card;
				try {
					card = await initializeCard(payments);
				} catch (e) {
					console.error('Initializing Card failed', e);
					return;
				}



				async function handlePaymentMethodSubmission(event, paymentMethod) {
					event.preventDefault();

					try {


                        cardButton.disabled = true;

                        //
                        if ( $('#form_send').valid() ) {


                            // disable the submit button as we await tokenization and make a
                            // payment request.
                            
                            const token = await tokenize(paymentMethod);
                            displayPaymentResults('SUCCESS');

                            //
                            postSale({
                                type: "square",
                                nonce: token,
                                idempotency_key: SqrGenUUID(),
                                location_id: square_loc_id
                            });
                            
                        }


					} catch (e) {
						cardButton.disabled = false;
						displayPaymentResults('FAILURE');
						console.error(e.message);
					}
				}



				const cardButton = document.getElementById(
					'card-button'
				);


				cardButton.addEventListener('click', async function (event) {
					await handlePaymentMethodSubmission(event, card);
				});

			}



			loadSquareContent();
		}
		/*--------------------------------- END SQUARE SECTION ---------------------------------------*/
		










    }
    return {init: moduleReady}
});