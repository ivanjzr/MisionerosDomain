define(function(){
    function moduleReady(modal, record_data){
        console.log(record_data);
    
        
        //
        $("#modal-title").html("Editar Familiar de " + record_data.customer_name);
        $('.btnAdd2').html("<i class='fa fa-pencil'></i> Actualizar");

        
        //
        $("#person_name").val(record_data.nombre);


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
        setTimeout(function(){
            //
            const birth_date = (record_data.birth_date && record_data.birth_date.date) ? moment(record_data.birth_date.date).format('DD MMM YYYY') : defaultBirthDate;
            $('#birth_date_2').datetimepicker('date', birth_date);
        }, 500);
        
        

        //
        setTimeout(function(){
            
        }, 500);


        //
        loadSelectAjax({
            id: "#relative_id",
            url: app.admin_url + "/sys/relatives/list",
            parseFields: function(item){
                return item.relative_type;
            },
            prependEmptyOption: true,
            default_value: record_data.relative_id,
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
                    url: app.admin_url + "/patients/" + record_data.customer_id + "/relatives/" + record_data.id,
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
                            app.Toast.fire({ icon: 'success', title: "Registro Actualizado Correctamente" });
                            //
                            $("#grid_customer_relatives").DataTable().ajax.reload();
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

        //
        $("#person_name").val(record_data.person_name);




    }
    return {init: moduleReady}
});