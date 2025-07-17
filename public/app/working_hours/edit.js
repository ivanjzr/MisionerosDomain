$(document).ready(function(){


    




        //
        app.getDayByCode = function(week_day){
            if (week_day==1){ return "Lunes"; }
            else if (week_day==2){ return "Martes"; }
            else if (week_day==3){ return "Miercoles"; }
            else if (week_day==4){ return "Jueves"; }
            else if (week_day==5){ return "Viernes"; }
            else if (week_day==6){ return "Sabado"; }
            else if (week_day==7){ return "Domingo"; }
            else {return "";}
        }


        var gridOptions = {
            gridId: "#grid_working_hours_list",
            url: app.admin_url + "/working-hours/" + record_id + "/items",
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "id", "data" : "name"},
                {"name": "week_day", "data" : function(obj){
                    return app.getDayByCode(obj.week_day);
                }},
                {"data" : function(obj){
                    //
                    if (obj.hora_inicio && obj.hora_fin){
                        return "de <strong>" + fmtTime(obj.hora_inicio.date) + "</strong> a <strong>" + fmtTime(obj.hora_fin.date) + "</strong>";
                    }
                    //
                    return "";
                }},
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
                { "targets": [0,1,2,4,5,6],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            //deferLoading: true,
            hdrBtnsSearch: false,
            order: [[ 3, "asc" ]],
            gridReady: function(){


                //
                $(".btn-eliminar").click(function(e){
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //var record_info = grid_table.row( getTrRespElem(this) ).data();
                    var del_id = $(this).data("id");
                    //console.log(record_info); return;


                    //
                    if (confirm("Eliminar registro con folio " + del_id + "?")){
                        //
                        $.ajax({
                            type:'POST',
                            url: app.admin_url + "/working-hours/" + record_id + "/items/del",
                            data: $.param({
                                id: del_id
                            }),
                            beforeSend: function (xhr) {
                                //
                            },
                            success:function(response){
                                //console.log(response.data);
                                if (response.id){
                                    //
                                    $("#grid_working_hours_list").DataTable().ajax.reload();
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
                    //console.log(data_info);

                    //
                    loadModalV2({
                        id: "modal-edit-record",
                        modal_size: "lg",
                        data: data_info,
                        /*onHide: function(){},*/
                        html_tmpl_url: "/app/working_hours/modals/list/add-edit-item.html?v=" + dynurl(),
                        js_handler_url: "/app/working_hours/modals/list/edit-item.js?v=" + dynurl(),
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){

                            //
                            enable_btns();
                            $('#modal-title').text("Editar Dia/Horas de Trabajo");
                            $('.btnAddEditWorkingHoursTitle').html("<i class='fa fa-edit'></i> Editar");

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




        app.onWorkingHoursReady = function(data){

            //
            $("#nombre").val(data.nombre);
            $("#hora_trab_name").text(data.nombre);

            if (data.active){$("#active").prop("checked", true);}

            $(".overlay").hide();
        }





        //
        $('#form_section').validate();
        //
        $('#form_section').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_section').valid() ) {
                //
                $('#form_section').ajaxSubmit({
                    url: app.admin_url + "/working-hours/" + record_id,
                    beforeSubmit: function(arr){
                        disable_btns();
                    },
                    success: function(response){
                        //
                        enable_btns();
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Registro Editado Correctamente" });
                            //
                            $("#grid_working_hours_list").DataTable().ajax.reload();
                            
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




      



         // Cargar los horarios
        app.loadWorkingHoursData = function(){
            $.ajax({
                type: 'GET',
                url: app.admin_url + "/working-hours/" + record_id,
                beforeSend: function (xhr) {
                    disable_btns();
                },
                success: function(data){
                    enable_btns();
                    //
                    if (data && data.id){
                        app.onWorkingHoursReady(data);
                    }
                },
                error: function(){
                    enable_btns();
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        };



        app.loadWorkingHoursData();






        //
        $("#btnAddWorkingHours").click(function(e){
            e.preventDefault();
            e.stopImmediatePropagation();

            
            //
            loadModalV2({
                id: "modal-add-record",
                modal_size: "lg",
                data: {},
                /*onHide: function(){},*/
                html_tmpl_url: "/app/working_hours/modals/list/add-edit-item.html?v=" + dynurl(),
                js_handler_url: "/app/working_hours/modals/list/add-item.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){

                    //
                    enable_btns();

                    // modal title
                    $('#modal-title').text("Agregar Dia/Horas de Trabajo");
                    $('.btnAddEditWorkingHoursTitle').html("<i class='fa fa-plus'></i> Crear");

                }
            });

        });


        
        
        //
        $('#btnReloadWorkingHoursItems').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            $("#grid_working_hours_list").DataTable().ajax.reload();

        });




});