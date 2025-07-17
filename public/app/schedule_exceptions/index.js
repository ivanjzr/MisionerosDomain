$(document).ready(function(){




      

        var gridOptions = {
            gridId: "#grid_schedule_exceptions",
            url: app.admin_url + "/working-hours/schedule-exceptions",
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : app.excepcionTipoField},
                {"name" : "motivo", "data" : "motivo"},
                {"data" : app.excepcionFechaField},
                {"data" : app.excepcionHorarioField},
                {"data" : app.excepcionRecurrenciaField},
                {"name": "activo", "data" : function(obj){ return fmtActiveV2(obj.activo); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-editar' data-info='"+data_info+"'><i class='fas fa-edit'></i></button>";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            pageLength: 100,
            columnDefs: [
                { "targets": [0,2,3,4],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            //deferLoading: true,
            hdrBtnsSearch: false,
            order: [[ 1, "desc" ]],
            gridReady: function(){


                //
                $(".btn-eliminar").click(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //var record_info = grid_table.row( getTrRespElem(this) ).data();
                    var record_id = $(this).data("id");
                    //console.log(record_info); return;


                    //
                    if (confirm("Eliminar registro con folio " + record_id + "?")){
                        //
                        $.ajax({
                            type:'POST',
                            url: app.admin_url + "/working-hours/schedule-exceptions/del",
                            data: $.param({
                                id: record_id
                            }),
                            beforeSend: function (xhr) {
                                //
                            },
                            success:function(response){
                                //console.log(response.data);
                                if (response.id){
                                    //
                                    $("#grid_schedule_exceptions").DataTable().ajax.reload();
                                }
                                //
                                else if (response.error){
                                    //
                                    app.Toast.fire({ icon: 'error', title: response.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                }
                            },
                            error: function(){
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        });

                    }

                });



                //
                $(".btn-editar").click(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();


                    //var record_info = grid_table.row( getTrRespElem(this) ).data();
                    var data_info = $(this).data("info"); 
                    data_info.endpoint_url = app.admin_url + "/working-hours/schedule-exceptions/",
                    //console.log(data_info);

                    //
                    loadModalV2({
                        id: "modal-edit-record",
                        modal_size: "lg",
                        data: data_info,
                        /*onHide: function(){},*/
                        html_tmpl_url: "/app/schedule_exceptions/modals/edit-schedule-exception.html?v=" + dynurl(),
                        js_handler_url: "/app/schedule_exceptions/modals/edit-schedule-exception.js?v=" + dynurl(),
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){

                            //
                            enable_btns();
                            $('#modal-title').text("Editar Excepcion de Horario");
                            $('.btnEdit2').html("<i class='fa fa-edit'></i> Editar");

                        }
                    });

                });


                $(".overlay").hide();

            }
        }

        //gridOptions.authToken = opts.authToken;
        //gridOptions.Utype = opts.Utype;

        // 
        dataGrid(gridOptions);





        //
        $("#btnAddScheduleException").click(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();

            
            //
            loadModalV2({
                id: "modal-add-record",
                modal_size: "lg",
                data: {},
                /*onHide: function(){},*/
                html_tmpl_url: "/app/schedule_exceptions/modals/add-schedule-exception.html?v=" + dynurl(),
                js_handler_url: "/app/schedule_exceptions/modals/add-schedule-exception.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){

                    //
                    enable_btns();

                    //
                    $("#motivo").focus();
                    $("#activo").prop("checked", true);


                    // modal title
                    $('#modal-title').text("Agregar Excepcion de Horario");
                    $('.btnAdd2').html("<i class='fa fa-plus'></i> Crear");
                    

                }
            });

        });


        
        
        //
        $('#btnReloadScheduleException').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            $("#grid_schedule_exceptions").DataTable().ajax.reload();

        });





   });