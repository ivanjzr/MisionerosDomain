$(document).ready(function(){


    

        var gridOptions = {
            gridId: "#grid_working_hours",
            url: app.admin_url + "/working-hours",
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "id", "data" : "nombre"},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";

                        var edit_url = "/admin/working-hours/" + obj.id + "/edit";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
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
                            url: app.admin_url + "/working-hours/del",
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
                                    $("#grid_working_hours").DataTable().ajax.reload();
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



                $(".overlay").hide();

            }
        }

        //gridOptions.authToken = opts.authToken;
        //gridOptions.Utype = opts.Utype;

        // 
        dataGrid(gridOptions);





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
                html_tmpl_url: "/app/working_hours/modals/add.html?v=" + dynurl(),
                js_handler_url: "/app/working_hours/modals/add.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){

                    //
                    enable_btns();
                    //
                    $("#nombre").focus();

                    // modal title

                }
            });

        });


        
        
        //
        $('#btnReloadWorkingHours').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            $("#grid_working_hours").DataTable().ajax.reload();

        });





});