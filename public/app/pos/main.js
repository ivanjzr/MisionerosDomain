(function ($) {
    'use strict';
    $(document).ready(function() {

        // Al inicio del JS, después de las constantes
        app.allowSelectEmployee = true;

        //
        const need_pin = $("#need_pin").val();
        const pos_user_id = $("#pos_user_id").val();
        const pos_user_name = $("#pos_user_name").val();
        const pos_register_id = $("#pos_register_id").val();
        const pos_register_name = $("#pos_register_name").val();
        app.sel_suc_id = parseInt($("#sel_suc_id").val());


        // Knockout ViewModel
        function PosViewModel() {

            //
            var self = this;
            
            // Observables
            self.items = ko.observableArray([]);
            self.payments = ko.observableArray([]);

            self.showItemsContainer = ko.observable(true);
            self.showPaymentContainer = ko.observable(false);

            self.showSelectItemsButton = ko.observable(false);
            //
            self.saleNotes = ko.observable('');
            self.selectedCustomer = ko.observable(null);
            self.selectedPromo = ko.observable(null);
            
            //
            self.allowSelectCustomer = ko.observable(false);
            
            

            // 
            self.exchange_rate = ko.observable(0);

            
            //
            self.promoInfo = ko.computed(function() {
                var promo = self.selectedPromo();
                if (promo && promo.valor) {
                    return promo.descripcion;
                }
                return '';
            });

            //
            self.promoDiscount = ko.computed(function() {
                var promo = self.selectedPromo();
                if (promo && promo.valor) {
                    return promo.es_porcentaje ? promo.valor + '%' : '-$' + promo.valor;
                }
                return '';
            });

            // Computed observables
            self.totalItems = ko.computed(function() {
                return self.items().length;
            });

            //
            self.hasCitaItem = ko.computed(function() {
                return self.items().some(function(item) {
                    return item.is_cita == 1 || item.is_cita === true;
                });
            });
            
            self.totalQty = ko.computed(function() {
                return self.items().reduce(function(sum, item) {
                    return sum + parseInt(item.qty()) || 0;
                }, 0);
            });


            //
            self.btnReload = function(){
                app.loadProducts();
            }


            //
            self.btnCloseRegister = function(){
                //
                loadModalV2({
                    id: "modal-close-register",
                    modal_size: "md",
                    data: {
                        pos_user_id,
                        pos_register_id
                    },
                    /*onHide: function(){},*/
                    html_tmpl_url: "/app/pos/modals/close-register.html?v=" + dynurl(),
                    js_handler_url: "/app/pos/modals/close-register.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        //disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                    }
                });
            }


            

            self.btnSelectCustomerCitas = function() {
                //
                if (posVM.selectedCustomer() && posVM.selectedCustomer().id){
                    //            
                    loadModalV2({
                        id: "modal-select-service",
                        modal_size: "md",
                        data: posVM.selectedCustomer(),
                        html_tmpl_url: "/app/pos/modals/select-service.html?v=" + dynurl(),
                        js_handler_url: "/app/pos/modals/select-service.js?v=" + dynurl(),
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){
                            enable_btns();
                        }
                    });
                } else {
                    app.Toast.fire({ icon: 'info', title:"Se requiere tener seleccionado un cliente" });
                }
                
            };

            //
            self.btnRemoveCustomer = function() {
                //
                self.selectedCustomer(null);
                // elimina citas encontradas
                self.items.remove(function(item) {return item.is_cita == 1 || item.is_cita === true;});
            };

            
            //
            self.btnRemovePromo = function() {
                self.selectedPromo(null);
            };

            //
            self.selectAllText = function(payment, event) {
                setTimeout(function() {
                    event.target.select();
                }, 1);
                return true;
            };
            


            //
            self.calculateDiscount = ko.computed(function() {
                var promo = self.selectedPromo();
                if (promo && promo.valor) {
                    var total = self.totalAmount();
                    var descuento = 0;
                    
                    if (promo.es_porcentaje) {
                        descuento = total * (parseFloat(promo.valor) / 100);
                    } else {
                        descuento = parseFloat(promo.valor);
                    }
                    
                    // Formatear para evitar decimales largos
                    //return Math.min(descuento, total);
                    return app.formatCurrency(Math.min(descuento, total));
                }
                return 0;
            });


            self.totalAmount = ko.computed(function() {
                return self.items().reduce(function(sum, item) {
                    return sum + (parseFloat(item.precio) * (parseInt(item.qty()) || 0));
                }, 0);
            });

            self.formattedDiscounts = ko.computed(function() {
                var descuento = self.calculateDiscount();
                return "$" + descuento.toFixed(2);
            });


            self.hasItems = ko.computed(function() {
                return self.items().length > 0;
            });

            
            self.showPaymentButton = ko.computed(function() {
                return self.hasItems() && self.showItemsContainer();
            });
            self.showApplyPayments = ko.computed(function() {
                return self.hasItems() && self.isFullyPaid();
            });
            self.showNotFullyPaid = ko.computed(function() {
                return !(self.hasItems() && self.isFullyPaid());
            });




            /**-------------- */
            self.finalTotal = ko.computed(function() {
                var total = self.totalAmount();
                var descuento = self.calculateDiscount();
                //
                //return Math.max(0, total - descuento);
                return app.formatCurrency(Math.max(0, total - descuento));
            });
            
            self.finalTotalUsd = ko.computed(function() {
                return (self.finalTotal() / self.exchange_rate());
            });
            self.totalPaid = ko.computed(function() {
                var sum = self.payments().reduce(function(sum, payment) {
                    var amount = parseFloat(payment.amount_mxn()) || 0;
                    return sum + (amount > 0 ? amount : 0);
                }, 0);
                //
                //return sum;
                return app.formatCurrency(sum);
            });
            
            self.remainingAmount = ko.computed(function() {
                var remaining = self.finalTotal() - self.totalPaid();
                //
                //return remaining > 0 ? remaining : 0;
                return remaining > 0 ? app.formatCurrency(remaining) : 0;
            });
            self.remainingAmountUsd = ko.computed(function() {
                return (self.remainingAmount() / self.exchange_rate());
            });

            self.changeAmount = ko.computed(function() {
                var remaining = self.finalTotal() - self.totalPaid();
                //
                //return remaining < 0 ? Math.abs(remaining) : 0;
                return remaining < 0 ? app.formatCurrency(Math.abs(remaining)) : 0;
            });
            self.changeAmountUsd = ko.computed(function() {
                return (self.changeAmount() / self.exchange_rate());
            });
            /**------------- */



            //
            self.formattedTotal = ko.computed(function() {
                return "$" + self.finalTotal().toFixed(2);
            });
            //
            self.isFullyPaid = ko.computed(function() {
                return self.totalPaid() >= self.finalTotal();
            });




            self.handleEnterKey = function(payment, event) {
                if (event.keyCode === 13) { // Enter key
                    self.validatePaymentAmount(payment);
                    return false; // Prevenir submit del form
                }
                return true;
            };


            //
            self.addPayment = function(paymentMethod, inputAmount) {
                var amountMxn, amountUsd = null;

                //
                if (paymentMethod.id == app.PAYMENT_METHOD_ID_EFECTIVO) {

                    // Efectivo: agregar total completo
                    amountMxn = self.remainingAmount();
                    
                    //
                    app.loadModalSetAmount({
                        payment_method: "Efectivo",
                        amount: amountMxn,
                        onContinue: function(new_amount){



                            // Fusionar efectivo - buscar si ya existe
                            var existingCash = ko.utils.arrayFirst(self.payments(), function(p) {
                                return p.payment_method_id == app.PAYMENT_METHOD_ID_EFECTIVO;
                            });
                            
                            if (existingCash) {
                                existingCash.amount_mxn(existingCash.amount_mxn() + parseFloat(new_amount));
                            } else {

                                //
                                self.payments.push({
                                    id: Date.now(),
                                    payment_method_id: paymentMethod.id,
                                    payment_type: paymentMethod.payment_type,
                                    amount_mxn: ko.observable(new_amount),
                                    amount_usd: ko.observable(null)
                                });
                                
                            }

                            // Focus al botón si está completamente pagado
                            setTimeout(function() {
                                if (self.isFullyPaid()) {
                                    $('.btnAddSale').focus();
                                }
                            }, 500);

                        }
                    });


                } else {
                    // USD y Tarjetas: agregar con 0
                    if (paymentMethod.id == app.PAYMENT_METHOD_ID_DOLARES) {


                        // Para USD: verificar que no exista ya uno 
                        var existingUsd = ko.utils.arrayFirst(self.payments(), function(p) {
                            return p.payment_method_id == app.PAYMENT_METHOD_ID_DOLARES;
                        });
                        
                        if (existingUsd) {
                            app.Toast.fire({ icon: 'info', title: "Ya existe un pago en dólares" });
                            return;
                        }

                        // debug por que no esta agarrando el exchange_rate
                        console.log((self.exchange_rate()));

                        //
                        app.loadModalSetAmount({
                            payment_method: "Dolares",
                            amount: 0, // Empezar en 0 para USD
                            onContinue: function(new_amount_usd){

                                //
                                var newPayment = {
                                    id: Date.now(),
                                    payment_method_id: paymentMethod.id,
                                    payment_type: paymentMethod.payment_type,
                                    amount_usd: ko.observable(new_amount_usd)
                                };
                                
                                // Para USD: amount_mxn es computed (se calcula automáticamente)
                                newPayment.amount_mxn = ko.computed(function() {
                                    return Math.floor((newPayment.amount_usd() || 0) * self.exchange_rate());
                                });

                                // Agregar al array de pagos
                                self.payments.push(newPayment);
                            }
                        });

                        
                        
                    } else {


                        // Para tarjetas: mostrar modal para capturar monto en MXN
                        app.loadModalSetAmount({
                            payment_method: paymentMethod.payment_type,
                            amount: self.remainingAmount(), // Sugerir el monto restante
                            onContinue: function(new_amount_mxn){
                                
                                // Para tarjetas: amount_mxn es observable normal
                                var newPayment = {
                                    id: Date.now(),
                                    payment_method_id: paymentMethod.id,
                                    payment_type: paymentMethod.payment_type,
                                    amount_mxn: ko.observable(new_amount_mxn),
                                    amount_usd: ko.observable(null)
                                };

                                // Agregar al array de pagos
                                self.payments.push(newPayment);
                            }
                        });

                        

                    }
                }
            };

            //
            self.removePayment = function(payment) {
                self.payments.remove(payment);
            };

            //
            self.validatePaymentAmount = function(payment) {
                if (payment.payment_method_id == app.PAYMENT_METHOD_ID_DOLARES) {
                    //
                    //var inputUsd = Math.floor(parseFloat(payment.amount_usd()) || 0);
                    var inputUsd = app.formatCurrency(parseFloat(payment.amount_usd()) || 0);
                    if (inputUsd < 0) inputUsd = 0;
                    payment.amount_usd(inputUsd);

                } else {

                    // Para otros: validar MXN directamente
                    var inputMxn = parseFloat(payment.amount_mxn()) || 0;
                    var amount = app.formatCurrency(inputMxn); 
                    //var amount = Math.floor(inputMxn);
                    if (amount < 0) amount = 0;
                    payment.amount_mxn(amount);
                }
                return true;
            };

            //
            self.increaseQty = function(item) {
                item.qty(item.qty() + 1);
            };
            //
            self.decreaseQty = function(item) {
                var newQty = item.qty() - 1;
                if (newQty >= 1) {
                    item.qty(newQty);
                } else {
                    item.qty(1);
                }
            };

            //
            self.validateQty = function(item) {
                var currentQty = parseInt(item.qty()) || 0;
                
                // Validar rango: mínimo 1, máximo 9999
                if (currentQty < 1 || isNaN(currentQty)) {
                    item.qty(1);
                } else if (currentQty > 9999) {
                    item.qty(9999);
                } else {
                    // Asegurar que sea entero (sin decimales)
                    item.qty(Math.floor(currentQty));
                }
                return true;
            };


            self.addNewItem = function(itemData){
                
                //
                var newItem = {
                    id: itemData.id,
                    prod_code: itemData.prod_code,
                    nombre: itemData.nombre,
                    precio: itemData.precio,
                    qty: ko.observable(1),
                    employee: itemData.employee || null,
                    is_cita: itemData.is_cita || false,
                    itemData: itemData
                };

                // Crear el computed después de definir qty
                newItem.formattedAmount = ko.computed(function() {
                    return "$" + (parseFloat(itemData.precio) * newItem.qty()).toFixed();
                });

                self.items.push(newItem);

                setTimeout(function() {
                    $('tbody[data-bind*="foreach: items"] tr:last').addClass('item-flash');
                    setTimeout(function() {
                        $('tbody[data-bind*="foreach: items"] tr:last').removeClass('item-flash');
                    }, 1500);
                }, 50);
            }

            
            // 
            self.addItem = function(itemData) {
                console.log("***AddItem: ", itemData);
                //
                if (itemData.tps=="s"){

                    //
                    loadModalV2({
                        id: "modal-select-contact",
                        modal_size: "md",
                        data: itemData,
                        html_tmpl_url: "/app/pos/modals/select-contact.html?v=" + dynurl(),
                        js_handler_url: "/app/pos/modals/select-contact.js?v=" + dynurl(),
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){
                            enable_btns();
                        },
                        onSubmit: function(newData){
                            //console.log("Employee selected data:", newData);
                            
                            // Para servicios, verificar si ya existe el mismo servicio con el mismo empleado
                            var existingItem = ko.utils.arrayFirst(self.items(), function(item) {
                                // Verificar si es el mismo servicio
                                if (item.id != newData.id) {
                                    return false;
                                }
                                
                                // Si ambos tienen empleado, verificar que sean el mismo
                                if (item.employee && newData.employee) {
                                    return item.employee.id == newData.employee.id;
                                }
                                
                                // Si ambos NO tienen empleado (son null), entonces son iguales
                                if (!item.employee && !newData.employee) {
                                    return true;
                                }
                                
                                // Si uno tiene empleado y el otro no, entonces son diferentes
                                return false;
                            });
                            
                            // Si ya existe el item con las mismas características
                            if (existingItem) {
                                
                                // Si ya existe y es cita mostramos error 
                                if (newData.is_cita){
                                    app.Toast.fire({ icon: 'info', title: "Ya has agregado esta cita previamente" });
                                } else {
                                    existingItem.qty(existingItem.qty() + 1);
                                }
                                
                                // Animar fila existente
                                setTimeout(function() {
                                    $('tbody[data-bind*="foreach: items"] tr').each(function() {
                                        var $row = $(this);
                                        if ($row.find('td:first').text() === existingItem.prod_code) {
                                            $row.addClass('item-flash');
                                            setTimeout(function() {
                                                $row.removeClass('item-flash');
                                            }, 1500);
                                        }
                                    });
                                }, 50);

                            } else {
                                self.addNewItem(newData);
                            }

                        }
                    });
                    
                    

                } else if (itemData.tps=="p"){
                    

                    //
                    var existingItem = ko.utils.arrayFirst(self.items(), function(item) {
                        return item.id == itemData.id;
                    });                
                    
                    // si ya existe el item lo sumamos
                    if (existingItem) {


                        // Si ya existe y es cita mostramos error 
                        if (itemData.is_cita){
                            app.Toast.fire({ icon: 'info', title: "Ya has agregado esta cita previamente" });
                        } 
                        // Si no es cita sumamos qty
                        else {
                            existingItem.qty(existingItem.qty() + 1);
                        }


                        // Animar fila existente
                        setTimeout(function() {
                            $('tbody[data-bind*="foreach: items"] tr').each(function() {
                                var $row = $(this);
                                if ($row.find('td:first').text() === existingItem.prod_code) {
                                    $row.addClass('item-flash');
                                    setTimeout(function() {
                                        $row.removeClass('item-flash');
                                    }, 1500);
                                }
                            });
                        }, 50);


                    } 
                    // si no existe lo agregamos
                    else {
                        self.addNewItem(itemData);
                    }
                    
                }


            };
            



            /*
            self.addItemPrevious = function(itemData) {
                console.log("***AddItem: ", itemData);

                //
                var existingItem = ko.utils.arrayFirst(self.items(), function(item) {
                    return item.id == itemData.id;
                });
                
                //
                if (existingItem) {


                    // Si ya existe y es cita mostramos error 
                    if (itemData.is_cita){
                        app.Toast.fire({ icon: 'info', title: "Ya has agregado esta cita previamente" });
                    } 
                    // Si no es cita sumamos qty
                    else {
                        existingItem.qty(existingItem.qty() + 1);
                    }


                    // Animar fila existente
                    setTimeout(function() {
                        $('tbody[data-bind*="foreach: items"] tr').each(function() {
                            var $row = $(this);
                            if ($row.find('td:first').text() === existingItem.prod_code) {
                                $row.addClass('item-flash');
                                setTimeout(function() {
                                    $row.removeClass('item-flash');
                                }, 1500);
                            }
                        });
                    }, 50);

                } else {


                    if (app.allowSelectEmployee){

                        //
                        if (itemData.tps=="s"){
                            //
                            loadModalV2({
                                id: "modal-select-contact",
                                modal_size: "md",
                                data: itemData,
                                html_tmpl_url: "/app/pos/modals/select-contact.html?v=" + dynurl(),
                                js_handler_url: "/app/pos/modals/select-contact.js?v=" + dynurl(),
                                onBeforeLoad: function(){
                                    disable_btns();
                                },
                                onInit: function(){
                                    enable_btns();
                                }
                            });

                        } else {
                            self.addNewItem(itemData);
                        }

                    } 
                    // si no permite seleccionar empleado enviamos directamente
                    else {
                        //
                        self.addNewItem(itemData);
                    }

                    
                    
                }
            };
            */



            //
            self.removeItem = function(item) {
                self.items.remove(item);
            };
            
            //
            self.resetItems = function() {
                self.items.removeAll();
            };


            //
            self.selectCustomer = function() {
                //
                loadModalV2({
                    id: "modal-select-contact",
                    modal_size: "md",
                    data: {},
                    html_tmpl_url: "/app/pos/modals/select-customer.html?v=" + dynurl(),
                    js_handler_url: "/app/pos/modals/select-customer.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });
            };

            //
            self.selectEmployee = function() {
                //
                loadModalV2({
                    id: "modal-select-contact",
                    modal_size: "md",
                    data: {},
                    html_tmpl_url: "/app/pos/modals/select-contact.html?v=" + dynurl(),
                    js_handler_url: "/app/pos/modals/select-contact.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });
            };

            //
            self.selectPromo = function() {
                //            
                loadModalV2({
                    id: "modal-select-promo",
                    modal_size: "md",
                    data: {},
                    html_tmpl_url: "/app/pos/modals/select-promo.html?v=" + dynurl(),
                    js_handler_url: "/app/pos/modals/select-promo.js?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });
            };

            self.getItemsForSale = function() {
                return self.items().map(function(item) {
                    return {
                        id: item.id,
                        nombre: item.nombre,
                        is_cita: item.is_cita,
                        qty: item.qty(),
                        employee: item.employee
                    };
                });
            };

            self.getPaymentsForSale = function() {
                return self.payments().map(function(payment) {
                    return {
                        payment_method_id: payment.payment_method_id,
                        payment_type: payment.payment_type,
                        amount_mxn: parseFloat(payment.amount_mxn()) || 0,
                        amount_usd: payment.amount_usd ? (parseFloat(payment.amount_usd()) || 0) : null
                    };
                }).filter(function(payment) {
                    return payment.amount_mxn > 0; // Solo pagos con monto mayor a 0
                });
            };

        }

        // Crear instancia del ViewModel 
        var posVM = new PosViewModel();
        
        // Aplicar bindings solo si existen los elementos
        setTimeout(function() {
            try {
                if (document.getElementById('ko_container')) {
                    ko.applyBindings(posVM, document.getElementById('ko_container'));
                }
                if (document.getElementById('ko_header')) {
                    ko.applyBindings(posVM, document.getElementById('ko_header'));
                }
            } catch(e) {
                console.log('Knockout binding error:', e);
            }
        }, 100);

        
        
        
        //
        app.printTicket = function(sale_id) {
            //
            const ticketUrl = app.admin_url + "/pos/main/" + sale_id + "/ticket";
            
            // Configuración específica para impresora térmica
            const printWindow = window.open(
                ticketUrl, 
                '_blank', 
                'width=320,height=600,scrollbars=no,resizable=no,menubar=no,toolbar=no,location=no,status=no'
            );
            
            // Esperar a que cargue y configurar impresión
            printWindow.onload = function() {
                // Configurar página para impresión térmica
                printWindow.document.head.insertAdjacentHTML('beforeend', `
                    <style>
                        @media print {
                            @page {
                                margin: 0;
                                size: 70mm auto;
                            }
                            body {
                                width: 70mm !important;
                                padding: 1mm 1mm 2mm 0.5mm !important;
                                font-size: 12px !important;
                            }
                        }
                    </style>
                `);
                
                // Auto-print después de cargar (MÉTODO 4)
                setTimeout(function() {
                    printWindow.focus(); // Asegurar foco
                    printWindow.print();
                    
                    // Cerrar automáticamente después de imprimir
                    setTimeout(function() {
                        //printWindow.close();
                    }, 2000);
                }, 1000);
            };
        };
        
        //
        app.postAdd = function(){
            //
            const cust_id = posVM.selectedCustomer() ? posVM.selectedCustomer().id : null;
            const prom_id = posVM.selectedPromo() ? posVM.selectedPromo().id : null;
            // 
            $.ajax({
                type: "POST",
                url: app.admin_url + "/pos/main/add",
                dataType: "json",
                data: JSON.stringify({
                    arr_products: posVM.getItemsForSale(), 
                    arr_payments: posVM.getPaymentsForSale(),
                    pos_user_id,
                    pos_register_id,
                    sale_notes: posVM.saleNotes(),
                    customer_id: cust_id,
                    promo_id: prom_id
                }),
                contentType: "application/json",
                timeout: 10000,
                success: function(data) {
                    if (data.id) {

                        //
                        app.Toast.fire({ icon: 'success', title: "Venta guardada Ok" });

                        //
                        setTimeout(function(){                            
                            //
                            posVM.resetItems()
                            app.printTicket(data.id);
                        }, 1000);

                        //
                        setTimeout(function(){
                            enable_btns();
                            $("#section-main").unblock();;
                        }, 2000);
                        
                    } else {

                        //
                        enable_btns();
                        $("#section-main").unblock();
                        //                    
                        const err = (data.error) ? data.error : "error al gestionar el registro";
                        app.Toast.fire({ icon: 'info', title: err });
                    }
                    
                },
                error: function(xhr, status, error) {
                    //console.error("Error loading events:", error);
                    enable_btns();
                    $("#section-main").unblock();
                    //
                    const err = "error al gestionar el registro";
                    app.Toast.fire({ icon: 'error', title: err });
                }
            });
        }


        // 
        posVM.addSale = function(){
            //
            if ( confirm("Agregar venta?") ){
                //
                block("#section-main", "Enviando venta..");
                disable_btns();
                //
                setTimeout(function(){
                    app.postAdd();
                }, 500);
            }
        }



        app.loadModalSetAmount = function(modal_data){
            //
            loadModalV2({
                id: "modal-set-amount",
                modal_size: "sm",
                data: modal_data,
                html_tmpl_url: "/app/pos/modals/set-amount.html?v=" + dynurl(),
                js_handler_url: "/app/pos/modals/set-amount.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    enable_btns();
                }
            });
        }


        app.loadSelectUser = function(){
            //
            loadModalV2({
                id: "modal-select-user",
                modal_size: "sm",
                data: {
                    pos_register_id,
                    pos_register_title: pos_register_name
                },
                html_tmpl_url: "/app/pos/modals/select-user.html?v=" + dynurl(),
                js_handler_url: "/app/pos/modals/select-user.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    enable_btns();
                }
            });
        }

        app.loadSetPin = function(){
            //
            loadModalV2({
                id: "modal-set-pin",
                modal_size: "sm",
                data: {
                    user_id: pos_user_id,
                    pos_register_id: pos_register_id,                    
                    user_name: pos_user_name,
                    pos_register_title: pos_register_name
                },
                html_tmpl_url: "/app/pos/modals/set-user-pin.html?v=" + dynurl(),
                js_handler_url: "/app/pos/modals/set-user-pin.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    enable_btns();
                }
            });
        }

        app.onProductsReady = function(arr_items){
            arr_items.forEach(function(product) {
                const productCard = `
                    <div class="col-12 col-md-3 mb-3">
                        <a href="#" class="text-decoration-none btnSelectItem" data-info='${JSON.stringify(product)}'>
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary">${product.prod_code}</span>
                                        <small class="text-muted">${product.servicio_duracion_minutos} min</small>
                                    </div>
                                    <h6 class="card-title">${product.nombre}</h6>
                                    <p class="card-text">
                                        <small class="text-muted">${product.category}</small>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-success">$${parseFloat(product.precio).toFixed(2)}</span>
                                        <small class="text-muted">por servicio</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                `;
                $('#products_list').append(productCard);
            });
        }

        app.bindItemsEvts = function(){
            $('.btnSelectItem').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                const item = $(this).data("info");
                const $card = $(this);
                
                // Animar card
                $card.addClass('product-flash');
                setTimeout(function() {
                    $card.removeClass('product-flash');
                }, 300);
                
                //console.log("--------btnSelectItem: ", item);
                posVM.addItem(item);
            });
        }

        app.loadProducts = function(){
            $('#products_list').empty();
            $.ajax({
                type: "GET",
                url: app.admin_url + "/productos-servicios/pos-available",
                success: function(data) {
                    if (data && data.length) {
                        app.onProductsReady(data);
                        app.bindItemsEvts();
                    } else {
                        const err = (data.error) ? data.error : "error al gestionar el registro";
                        app.Toast.fire({ icon: 'error', title: err });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading events:", error);
                    enable_btns();
                }
            });
        }


 

        //
        app.onPaymentMethodsReady = function(arr_items){
            //
            $('#payments_methods').empty();
            //            
            arr_items.forEach(function(item) {
                const icon = item.payment_type.includes('Efectivo') ? 'fas fa-money-bill-wave' : 
                            item.payment_type.includes('Tarjeta') ? 'fas fa-credit-card' : 'fas fa-coins';
                
                const productCard = `
                    <div class="col-4 col-md-3 mb-3">
                        <button class="btn btn-primary btn-lg w-100 h-100 btnAddPayment" 
                                data-info='${JSON.stringify(item)}' style="min-height: 80px;">
                            <div>
                                <i class="${icon} fa-2x mb-2"></i><br>
                                <span class="fw-bold">${item.payment_type}</span>
                            </div>
                        </button>
                    </div>
                `;
                $('#payments_methods').append(productCard);
            });
        }



        app.bindPaymentsMethodsEvts = function(){

            //
            $('.btnAddPayment').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                const paymentMethod = $(this).data("info");
                const remaining = posVM.remainingAmount();
                
                if (remaining <= 0) {
                    app.Toast.fire({ icon: 'info', title: "ya esta completado el pago" });
                    return;
                }
                
                // Validaciones según tipo de pago
                if (paymentMethod.id == app.PAYMENT_METHOD_ID_EFECTIVO) {
                    // Efectivo: verificar si ya existe
                    var existingCash = ko.utils.arrayFirst(posVM.payments(), function(p) {
                        return p.payment_method_id == app.PAYMENT_METHOD_ID_EFECTIVO;
                    });
                    
                    if (existingCash) {
                        //
                        app.Toast.fire({ icon: 'info', title: "Ya existe un pago en efectivo. Se sumará al existente" });
                    }
                    
                } else if (paymentMethod.id == app.PAYMENT_METHOD_ID_DOLARES) {
                    // USD: verificar si ya existe
                    var existingUsd = ko.utils.arrayFirst(posVM.payments(), function(p) {
                        return p.payment_method_id == app.PAYMENT_METHOD_ID_DOLARES;
                    });
                    
                    if (existingUsd) {
                        app.Toast.fire({ icon: 'info', title: "Ya existe un pago en dólares" });
                        return;
                    }
                    
                } else {
                    // Tarjetas: verificar si hay pagos en 0
                    var hasZeroPayments = ko.utils.arrayFirst(posVM.payments(), function(p) {
                        return p.payment_method_id == paymentMethod.id && (parseFloat(p.amount_mxn()) || 0) === 0;
                    });
                    
                    if (hasZeroPayments) {
                        app.Toast.fire({ icon: 'info', title: "Complete el pago anterior antes de agregar otro" });
                        return;
                    }
                }
                
                posVM.addPayment(paymentMethod);
            });


        }


        //
        app.processPayment = function(paymentMethod, amount) {
            let paymentData = {
                payment_method_id: paymentMethod.id,
                payment_type: paymentMethod.payment_type,
                amount_mxn: amount
            };
            
            // Si es dólares, calcular cantidad en dólares
            if (paymentMethod.id == app.PAYMENT_METHOD_ID_DOLARES) {
                paymentData.amount_usd = (amount / posVM.exchange_rate()).toFixed(2);
                console.log(`Cobrando: $${amount} MXN (${paymentData.amount_usd} USD)`);
            }
            
            // Aquí procesas el pago
            console.log('Procesando pago:', paymentData);
            
            // TODO: Enviar a tu API de pagos
            app.addPaymentToSale(paymentData);
        };

        //
        app.loadPaymentsMethods = function(){
            //
            $.ajax({
                type: "GET",
                url: app.admin_url + "/sys/pos-payments-methods",
                success: function(data) {
                    if (data && data.length) {
                        app.onPaymentMethodsReady(data);
                        app.bindPaymentsMethodsEvts();
                    } else {
                        const err = (data.error) ? data.error : "error al gestionar el registro";
                        app.Toast.fire({ icon: 'error', title: err });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error loading events:", error);
                    enable_btns();
                }
            });
        }
        
        // Inicialización
        if (pos_user_id){
            if( need_pin && (need_pin == 1) ){
                app.loadSetPin();
            }
        } else {
            app.loadSelectUser();
        }


       
        // Select2 para productos
        if ($("#product_id").length){
            $.S2Ext({
                S2ContainerId: "product_id",
                placeholder: "...buscar producto/servicio",
                language: {
                    noResults: function(){ return ""; },
                    searching: function(){ return ""; }
                },
                allowClear: true,
                minimumInputLength: 2,
                minimumResultsForSearch: "-1",
                remote: {
                    qs: function(){
                        return {};
                    },
                    url: app.admin_url + "/productos-servicios/search",
                    dataType: 'json',
                    delay: 250,
                    processResults: function (response, page) {
                        return {
                            results: response
                        };
                    },
                    cache: false,
                    templateResult: app.templateResultProduct,
                    templateSelection: app.templateSelectionProduct,
                },
                onChanged: function(sel_id, data){
                    console.log("--------s2 onChanged: ", data);
                    //
                    posVM.addItem(data);
                    setTimeout(function(){
                        s2ResetValue("#product_id");
                    }, 250);

                },
                onClose: function(){
                    $("#sel_customer_people_container").hide();
                }
            });
        }

        // Métodos para manejar navegación
        posVM.btnShowPayments = function() {

            if (posVM.hasItems()) {
                //
                posVM.showItemsContainer(false);
                posVM.showPaymentContainer(true);
                posVM.showSelectItemsButton(true);
            } else {
                app.Toast.fire({ icon: 'info', title: "no tiene pagos" });
            }
        };

        // 
        posVM.btnGoToItems = function() {
            //
            if (posVM.payments().length > 0) {
                var confirmMsg = "Tienes pagos aplicados. Al agregar más productos los totales cambiarán automáticamente. ¿Continuar?";
                if (!confirm(confirmMsg)) {
                    return; // No hacer nada si cancela
                }
            }            
            //
            posVM.showPaymentContainer(false);
            posVM.showItemsContainer(true);
            posVM.showSelectItemsButton(false);
        };

        //
        posVM.resetItems = function() {
            posVM.items.removeAll();
            posVM.payments.removeAll();
            posVM.saleNotes('');
            posVM.showItemsContainer(true);
            posVM.showPaymentContainer(false);
            posVM.showSelectItemsButton(false);
            //
            posVM.selectedCustomer(null);
            posVM.selectedPromo(null);
        };


        
        

        $('.btnSetPin').click(function(e) {
            e.preventDefault();
            if( need_pin && (need_pin == 1) ){
                app.loadSetPin();
            }
        });

        

        $('.btnSelectUser').click(function(e) {
            e.preventDefault();

            if ( posVM.hasItems() && confirm("tiene una venta en curso, si cambia de usuario se reiniciara, esta bien?")) {
                app.loadSelectUser();
            } else {
                app.loadSelectUser();
            }
            
        });


        

        

        


        // Custom events
        $(document).on("show_pos", function(){
            $("#pos_container").show();
        });



        // cargar init
        app.loadProducts();
        app.loadPaymentsMethods();


        //
        $(document).on('customerSelected', function(event, customerData) {
            //console.log("***customerSelected: ", customerData);
            posVM.selectedCustomer(customerData);
        });

        //
        $(document).on('serviceSelected', function(event, selectedService) {
            //console.log("***serviceSelected: ", selectedService);
            posVM.addItem(selectedService);
        });

        //
        $(document).on('promoSelected', function(event, selectedPromo) {
            //console.log("***promoSelected: ", selectedPromo);
            posVM.selectedPromo(selectedPromo);
        });

        /*
        $(document).on('onEmployeeSelected', function(event, itemData) {
            //console.log("***onEmployeeSelected: ", itemData);
            posVM.addNewItem(itemData);
        });
        */

        

        //
        app.initPosConfig = function(){
            //
            block("#section-main", "Loading...");
            //disable_btns();
            //            
            $.ajax({
                type: "GET",
                url: app.admin_url + "/pos/config",
                success: function(data) {                    
                    //
                    if (data && data.exchange_rate){
                        posVM.exchange_rate(data.exchange_rate); 
                    }
                    //enable_btns();
                    $("#section-main").unblock();
                },
                error: function(xhr, status, error) {
                    console.error("Error: " + error);
                    //enable_btns();
                    $("#section-main").unblock();;
                }
            });
        };



        app.initPosConfig();


    });
})(jQuery);