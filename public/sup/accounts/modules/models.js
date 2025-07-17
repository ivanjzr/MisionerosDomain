define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_account_models",
            section_title: "Accounts Models",
            data: section_data,
            section_title_singular: "Model",
            btnAddRecord: "#btnAddModel",
            scripts_path: "/sup/accounts/modules",
            endpoint_url: section_data.endpoint_url,
            gridOptions:{
                pageLength: 999,
                columns: [
                    {"data": function(obj){
                            //
                            var str_info = "<div>";
                            //
                            str_info += "<h4 style='padding:0;margin:0;'>" + obj.nombre + "</h4><small>" + " " + obj.clave + " " + safeNulValue(obj.model_name) + "</small><br />" + "<i style='color:green;' class='fas "+obj.fa_icon+"'></i> <small>" + obj.fa_icon + "</small>"
                            //
                            str_info += "</div>";
                            //
                            return str_info;
                        }},
                    {"data": function(obj){
                        //
                        var str_children = "";
                        //
                        if (obj.children && obj.children.length){
                            $.each(obj.children, function(idx, item){
                                //
                                var data_info = JSON.stringify(item);
                                //
                                var str_btns = "<div class='text-center'>";
                                //
                                if ( item.account_id && item.acct_active ){
                                    str_btns += " <button type='button' class='btn btn-sm btn-flat btn-warning btn-set-mode' data-info='"+data_info+"'><i class='fas fa-remove'></i> Deshabilitar </button>";
                                } else {
                                    str_btns += " <button type='button' class='btn btn-sm btn-flat btn-success btn-set-mode' data-info='"+data_info+"'><i class='fas fa-check'></i> Habilitar </button>";
                                }
                                //
                                str_btns += "</div>";
                                //
                                str_children += "<div style='border-bottom: 1px solid green;margin: 12px;'>" + item.nombre + " - " + item.clave + " <small>" + item.model_name + "</small><br />" + "<i style='color:green;' class='fas "+item.fa_icon+"'></i> <small>" + item.fa_icon + "</small>"+str_btns+"</div>";
                            });
                        }
                        //
                        return str_children;
                        }},
                    {"data": function(obj){
                            //
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            if ( obj.account_id && obj.acct_active ){
                                str_btns += " <button type='button' class='btn btn-sm btn-flat btn-warning btn-set-mode' data-info='"+data_info+"'><i class='fas fa-remove'></i> Deshabilitar </button>";
                            } else {
                                str_btns += " <button type='button' class='btn btn-sm btn-flat btn-success btn-set-mode' data-info='"+data_info+"'><i class='fas fa-check'></i> Habilitar </button>";
                            }
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 1, 2],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onGridReady: function(opts){


                //
                $(".btn-set-mode").click(function(e){
                    e.preventDefault();


                    //var record_info = grid_table.row( getTrRespElem(this) ).data();
                    var record_info = $(this).data("info");
                    //console.log(record_info); return;


                    //
                    var str_msg = "";
                    //
                    if ( record_info.account_id && record_info.acct_active ){
                        str_msg = "Deshabilitar registro con folio " + record_info.id + "?";
                    } else {
                        str_msg = "Habilitar registro con folio " + record_info.id + "?";
                    }


                    //
                    if (confirm(str_msg)){
                        //
                        $.ajax({
                            type:'POST',
                            url: opts.endpoint_url + "/set-mode",
                            data: $.param({
                                id: record_info.id
                            }),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            success:function(response){
                                //console.log(response.data);
                                if (response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Registro actualizado correctamente" });
                                    $("#grid_account_models").DataTable().ajax.reload();
                                }
                                //
                                else if (response.error){
                                    //
                                    app.Toast.fire({ icon: 'error', title: response.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                                }
                            },
                            error: function(){
                                app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                            }
                        });
                    }

                });




            },
            onSectionReady: function(opts){


                //
                $('#btnReloadModels').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_account_models").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});