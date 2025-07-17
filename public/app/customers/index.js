(function ($) {
    'use strict';



    //
    const section_name = $("#section_name").val();


    //
    function filterGrid(){
        //
        //var filter_sale_type_id = $("#filter_sale_type_id").val();
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/customers");
        $("#grid_section").DataTable().ajax.reload();
    }



    //
    app.createSection({
        section_title: section_name,
        section_title_singular: section_name,
        scripts_path: "/app/customers",
        modalAddHtmlName: "add-customer.html",
        endpoint_url: app.admin_url + "/customers",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name": "id", "data" : "id"},
                {"name": "name", "data" : "name"},
                {"name": "id", "data" : function(obj){ 
                    return safeNulValue(obj.phone_cc) + " " + safeNulValue(obj.phone_number);
                }},
                {"name": "id", "data" : "email"},
                {"data" : function(obj){return fmtDateEng(obj.datetime_created.date); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        var clinical_url = "/admin/customers/" + obj.id + "/clinical-records/index";
                        str_btns += " <a href='"+clinical_url+"' class='btn btn-sm btn-flat btn-success' title='Expediente Clínico'><i class='fas fa-file-medical'></i> Expediente </a> ";                        
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-outline-primary btn-send-mail' data-info='"+data_info+"'><i class='fas fa-envelope'></i></button> ";
                        //
                        var edit_url = "/admin/customers/" + obj.id + "/edit";
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-primary' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                {
                    "targets": [2, 3, 4],
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            deferLoading: true,
            hdrBtnsSearch: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){
            
            //
            loadSelectAjax({
                id: "#phone_country_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return "+" + item.phone_cc + " (" + item.abreviado + ")";
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                default_value: app.ID_PAIS_EU,
                enable: true
            });


            //
            var defaultBirthDate = moment().subtract(35, 'years');
            //
            $('#birth_date').datetimepicker({
                format: 'DD/MM/YYYY',
                locale: 'es-mx',
                date: defaultBirthDate,
                buttons: {
                    showToday: false,
                    showClear: false,
                    showClose: false
                },
                icons: app.tdBs5Icons,
                viewMode: 'years', 
                    minViewMode: 'days',
                pickTime: false,
                calendarWeeks: false,
                showTodayButton: false
            });

            //
            $("#name").focus();
            $('#active').attr("checked", true);
        },
        //
        beforeSubmit: function(arr){

            //
            arr.push({
                name: "birth_date",
                value: $('#birth_date').datetimepicker('date').format('YYYY-MM-DD')
            });
            
        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){
            //
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){
            //
        },
        onGridReady: function(opts){


            

            const modal_title = "modal-send-email";
            

            //
            $(".btn-send-mail").click(function(e){
                e.preventDefault();                
                //
                var data_info = $(this).data("info");
                //console.log(data_info);
                //
                loadModalV2({
                    id: modal_title,
                    modal_size: "md",
                    html_tmpl_url: "/app/customers/modals/send-customer-email.html?v=" + dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                        //
                        $("#modal-title").html("Enviar mensaje a " + data_info.customer_name);
                        //
                        $("#form_send_customer_mail").validate();
                        //
                        $("#form_send_customer_mail").submit(function(e) {
                            e.preventDefault();
                            //
                            if ( $("#form_send_customer_mail").valid() ) {

                                //
                                $("#form_send_customer_mail").ajaxSubmit({
                                    url: app.admin_url + "/customers/send-email/" + data_info.id,
                                    beforeSubmit: function(arr){
                                        //
                                        enable_btns();
                                        preload(".section-preloader, .overlay", true);
                                    },
                                    success: function(send_response){
                                        //
                                        enable_btns();
                                        preload(".section-preloader, .overlay");
                                        //
                                        if (send_response && send_response.main_msg && send_response.main_msg.success){

                                            //
                                            app.Toast.fire({ icon: 'success', title: "Mensaje enviado a " + data_info.customer_name });
                                            $("#" + modal_title).find('.modal').modal("hide");


                                        }
                                        //
                                        else if (send_response.error){
                                            app.Toast.fire({ icon: 'error', title: send_response.error });
                                        }
                                        //
                                        else {
                                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                        }
                                    },
                                    error: function(response_error){
                                        enable_btns();
                                        preload(".section-preloader, .overlay");
                                        //
                                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                    }
                                });

                            }
                        });



                    }
                });


            });

        },
        onSectionReady: function(opts){



            
            

        }
    });


    
    setTimeout(function(){
        filterGrid();
    }, 1000);
    


})(jQuery);