define(function(){
    function moduleReady(section_data){
        console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_evento_fechas",
            section_title: "Fechas",
            data: section_data,
            btnAddRecord: "#btnModalAddFecha",
            formName: "#form_fechas",
            btnReloadGrid: "#btnReloadFechas",
            section_title_singular: "Fecha",
            scripts_path: "/app/events/modules",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/fechas",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "evento_id", "data" : "evento_id"},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.event_date.date); }},
                    {"name": "event_info", "data" : "event_info"},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 3, 5],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            beforeSubmit: function(arr){
                //
                var this_date = $('#event_date').datetimepicker('date');
                //
                arr.push({
                    name: "event_date",
                    value: moment(this_date).format("YYYY-MM-DD")
                });
            },
            onAddEditReady: function(opts){

                //
                $("#event_date").datePickerTask({
                    storeId: "event_date",
                    opts:{
                        autoclose: true,
                        defaultDate: moment(),
                        ignoreReadonly: false,
                        format: 'MMMM-DD-YYYY'
                    },
                    onChange: function(date){
                        /**/
                    }
                });

            },
            onSectionReady: function(opts){






                return;

                //
                $('#form_fechas').validate();
                //
                $('#form_fechas').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_fechas').valid() ) {
                        //
                        $('#form_fechas').ajaxSubmit({
                            url: section_data.opts.endpoint_url + "/" + section_data.id + "/fechas",
                            beforeSubmit: function(arr){
                                disable_btns();
                            },
                            success: function(response){
                                //
                                enable_btns();
                                //
                                if (response && response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                                    //
                                    $("#grid_evento_fechas").DataTable().ajax.reload();
                                    $("#event_date").val("");
                                    //
                                    section_data.opts.loadData();
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





            }
        });



    }
    return {init: moduleReady}
});