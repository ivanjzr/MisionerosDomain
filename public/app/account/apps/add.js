define(function(){
    function moduleReady(modal, customer_data){
        console.log(customer_data);
    
        
        //
        $("#modal-title").html("Agregar Familiar para " + customer_data.customer_name);
        $('.btnAdd2').html("<i class='fa fa-plus'></i> Crear");

        

        //
        const defaultBirthDate = moment().subtract(35, 'years');
        //
        $('#birth_date_2').datetimepicker({
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
        loadSelectAjax({
            id: "#relative_id",
            url: app.admin_url + "/sys/relatives/list",
            parseFields: function(item){
                return item.relative_type;
            },
            prependEmptyOption: true,
            emptyOptionText: "--seleccionar",
            enable: true
        });



        //
        $('#form_familiares').validate();
        //
        $('#form_familiares').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_familiares').valid() ) {
                //
                $('#form_familiares').ajaxSubmit({
                    url: app.admin_url + "/patients/" + customer_data.id + "/relatives",
                    beforeSubmit: function(arr){
                        disable_btns();

                        //
                        arr.push({
                            name: "birth_date",
                            value: $('#birth_date_2').datetimepicker('date').format('YYYY-MM-DD')
                        });
                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                            //
                            if ($("#grid_customer_relatives").length) {
                                
                                //
                                $("#grid_customer_relatives").DataTable().ajax.reload();

                            } else if ($("#form_add_reservar").length) {
                                
                                //
                                $("#form_add_reservar").trigger("reloadRelativesList");
                            }
                            
                            //                            
                            $("#person_name").val("");
                            $("#relative_id").val("");
                            //
                            $("#modal-relative").find('.modal').modal("hide");
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
                    error: function(response){
                        enable_btns();
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });

            }
        });




        // def focus
        $("#person_name").focus();




    }
    return {init: moduleReady}
});