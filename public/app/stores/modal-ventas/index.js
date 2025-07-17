define(function(){
    function moduleReady(modal, section_data){
        //console.log(modal, section_data);



        //
        $('#registro_folio').text(section_data.id);
        //
        if (section_data.datetime_created && section_data.datetime_created.date){
            $('#registro_fecha_creacion').text(fmtDateSpanish(section_data.datetime_created.date, true));
        }
        //
        $('.edit-mode-only').show();

        // modal title
        $('#modal-title').text("Ventas Negocio #" + section_data.id);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Agregar");



        //
        dataGrid({
            gridId: "#grid_stores_sales",
            url: app.admin_url + "/stores/" + section_data.id + "/sales",
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                        //
                        var str_vals = "<div>";
                        //
                        $.each(obj.sale_items, function(idx, item){
                            //
                            var str_periodicidad = "";
                            if (item.periodicidad_id){
                                str_periodicidad = "<strong style='color:green;'>" + ucFirst(item.periodicidad) + "</strong> - ";
                            }
                            str_vals += "<div style=''>• <strong>(x" + parseInt(item.qty) + ")</strong> " + str_periodicidad + item.item_info + "</div>";
                        });
                        //
                        str_vals += "</div>";
                        return str_vals
                    }},
                //
                {"data" : function(obj){
                        //
                        var str_info = "<ul>";
                        //
                        str_info += "<li><strong>Subtotal: </strong> " + obj.sub_total +"</li>";
                        //
                        if ( obj.discount_percent > 0 || obj.tax_amount > 0  ){
                            //
                            if ( obj.discount_percent > 0 ){
                                str_info += "<li><strong>Discount %</strong>" + obj.discount_percent +": -" + obj.discount_amount + " </li>";
                            } else if ( obj.discount_amount > 0 ){
                                str_info += "<li><strong>Discount -</strong>" + obj.discount_amount + " + </li>";
                            }
                            //
                            if ( obj.tax_amount > 0 ){
                                str_info += "<li><strong>Tax %</strong>" + obj.tax_percent +": " + obj.tax_amount + " </li>";
                            }
                        }
                        //
                        str_info += "<li><strong>Total: </strong> " + obj.grand_total +"</li>";
                        //
                        str_info += "</ul>";
                        return str_info;
                    }},
                {"data" : function(obj){
                        //
                        var str_info = "<ul>";
                        //
                        $.each(obj.sale_payments, function(idx, item){
                            //
                            var payment_status = "";
                            if ( parseInt(item.payment_status_id) === 2 ){
                                payment_status = "<span class='badge badge-secondary'> *" + item.payment_status + " </span>";
                            } else {
                                payment_status = "<span class='badge badge-success'> " + item.payment_status + " </span>";
                            }
                            //
                            var payment_link = "";
                            if ( item.payer_name ){
                                payment_link = "<a href='" + item.payer_name + "' class='btn btn-link' target='_blank'><span class='fa fa-external-link-alt'></span> Voucher Oxxo </a>";
                            }
                            //
                            str_info += "<li><strong style='color:green;'> " + item.payment_type + " </strong> <br />" + item.amount + " <small style='color:gray;'>" + item.tipo_moneda + "</small> " + payment_link + payment_status +"</li>";
                        });
                        //
                        str_info += "</ul>";
                        return str_info;
                    }},
                {"data" : function(obj){ return fmtDateEng(obj.datetime_created.date); }},
                {"data" : function(obj){
                        //
                        var str_notes = "";
                        //
                        if (obj.status_notes){
                            str_notes = "<br /><span style='font-size:14px;color:orangered;font-weight: bold;'>" + obj.status_notes + "</span>";
                        }
                        return obj.status_title + str_notes;
                    }},
                {"width": "200px", "data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <a href='#!' class='btn btn-sm btn-info btn-complete-payment' data-info='"+data_info+"'><i class='fas fa-check-circle'></i> Completar Pago </a> ";
                        str_btns += " <a href='#!' class='btn btn-sm btn-danger btn-eliminar-record' data-info='"+data_info+"'><i class='fas fa-trash'></i></a>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 4],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]],
            gridReady: function(){


                //
                $('.btn-complete-payment').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var subsection_data = $(this).data("info");
                    disable_btns();

                    //
                    if (confirm("Completar pago para venta con folio " + subsection_data.id + "?")){
                        //
                        $.ajax({
                            type:'POST',
                            url: app.admin_url + "/stores/" + section_data.id + "/sales/complete",
                            data: $.param({
                                id: subsection_data.id
                            }),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            success:function(response2){
                                //console.log(response2.data);
                                //
                                enable_btns();
                                if (response2.id){
                                    //
                                    $("#grid_section").DataTable().ajax.reload();
                                    $("#grid_stores_sales").DataTable().ajax.reload();
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Pago completado correctamente" });
                                }
                                //
                                else if (response2.error){
                                    app.Toast.fire({ icon: 'error', title: response2.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                }
                            },
                            error: function(){
                                enable_btns();
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        });
                    }

                });



                //
                $('.btn-eliminar-record').click(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    var subsection_data = $(this).data("info");

                    //
                    if (confirm("Eliminar registro con folio " + subsection_data.id + "?")){
                        //
                        $.ajax({
                            type:'POST',
                            url: app.admin_url + "/stores/" + section_data.id + "/sales/del",
                            data: $.param({
                                id: subsection_data.id
                            }),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            success:function(response2){
                                //console.log(response2.data);
                                enable_btns();
                                if (response2.id){
                                    //
                                    $("#grid_section").DataTable().ajax.reload();
                                    $("#grid_stores_sales").DataTable().ajax.reload();
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro eliminado correctamente" });
                                }
                                //
                                else if (response2.error){
                                    app.Toast.fire({ icon: 'error', title: response2.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                }
                            },
                            error: function(){
                                enable_btns();
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        });
                    }

                });

            }
        });






        //
        loadSelectAjax({
            id: "#suscripcion_id",
            url: app.admin_url + "/list-subscriptions",
            parseFields: function(item){
                return ucFirst(item.periodicidad) + " -  $" + item.precio;
            },
            onChange: function(sel_item){
                //
                var subs_info = $("#suscripcion_id").find(':selected').data('info');
                var str_subs_nombre = (subs_info && subs_info.nombre) ? subs_info.nombre : "";
                $("#subs_info").html(str_subs_nombre);
            },
            prependEmptyOption: true,
            emptyOptionText: "--selecciona",
            enable: true
        });







        //
        $('#form_add_sale').validate();
        //
        $('#form_add_sale').submit(function(e) {
            e.preventDefault();
            //
            if ( $('#form_add_sale').valid() ) {


                //
                $('#form_add_sale').ajaxSubmit({
                    url: app.admin_url + "/stores/" + section_data.id + "/sales",
                    beforeSubmit: function(arr){
                        disable_btns();
                        preload(".section-preloader", true)
                    },
                    success: function(response2){
                        //
                        enable_btns();
                        preload(".section-preloader")
                        //
                        if (response2 && response2.id){
                            //
                            $("#grid_section").DataTable().ajax.reload();
                            $("#grid_stores_sales").DataTable().ajax.reload();
                            //
                            app.Toast.fire({ icon: 'success', title: "Venta Agregada Ok!" });
                        }
                        //
                        else if (response2.error){
                            app.Toast.fire({ icon: 'error', title: response2.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    },
                    error: function(response2){
                        enable_btns();
                        preload(".section-preloader")
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });

            }
        });


    }
    return {init: moduleReady}
});






