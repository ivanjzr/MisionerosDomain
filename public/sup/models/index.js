(function ($) {
    'use strict';




    //
    app.createSection({
        section_title: "Modelos",
        section_title_singular: "Modelo",
        scripts_path: "/sup/models",
        endpoint_url: app.supadmin_url + "/models",
        gridOptions:{
            pageLength: 999,
            columns: [
                { "name" : "orden", "data" : "orden" },
                { "width":75, "data": function(obj){
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        if (obj.prev_orden){
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-order' data-new_orden='up' data-id='"+obj.id+"'><i class='fas fa-arrow-up'></i></button> ";
                        }
                        //
                        if (obj.next_orden){
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-order' data-new_orden='down' data-id='"+obj.id+"'><i class='fas fa-arrow-down'></i></button>";
                        }
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
                {"visible": false, "name" : "id", "data" : "id"},
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
                                str_children += "<div style='border-bottom: 1px solid green;margin: 12px;'>";
                                //
                                var str_is_only_admin = "<small class='text-danger'>NO - Es Solo Admin</small>";
                                if (item.is_only_admin){
                                    str_is_only_admin = "<small class='text-success'>SI Es Solo Admin</small>";
                                }
                                //
                                var str_is_menu_expandible = "<small class='text-danger'>NO Menu Expandible</small>";
                                if (item.is_menu_expandible){
                                    str_is_menu_expandible = "<small class='text-success'>SI Menu Expandible</small>";
                                }
                                str_children += item.orden + " - " + item.nombre + " - " + item.clave + " <small>" + item.model_name + "</small><br />" + "<i style='color:green;' class='fas "+item.fa_icon+"'></i> <small>" + item.fa_icon + "</small>";
                                str_children += "<div>" + str_is_only_admin + " / " + str_is_menu_expandible + "</div>";
                                //
                                str_children += "<div class='text-center'>";
                                //
                                if (item.prev_orden){
                                    str_children += " <button type='button' class='btn btn-sm btn-flat btn-info btn-order' data-new_orden='up' data-id='"+item.id+"'><i class='fas fa-arrow-up'></i></button> ";
                                }
                                //
                                if (item.next_orden){
                                    str_children += " <button type='button' class='btn btn-sm btn-flat btn-info btn-order' data-new_orden='down' data-id='"+item.id+"'><i class='fas fa-arrow-down'></i></button>";
                                }
                                //
                                str_children += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                                str_children += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+item.id+"'><i class='fas fa-trash'></i></button>";
                                str_children += "</div>";
                                //
                                str_children += "</div>";
                            });
                        }
                        //
                        return str_children;
                    }},
                {"name": "is_menu_expandible", "data" : function(obj){ return fmtActive(obj.is_menu_expandible); }},
                {"name": "is_only_admin", "data" : function(obj){ return fmtActive(obj.is_only_admin); }},
                {"data": function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }}
            ],
            columnDefs: [
                { "targets": [0, 1, 2, 3],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]],
        },
        onAddReady: function(data){
            //
        },
        onEditReady: function(section_data, opts){
            //
            $('#nombre').val(section_data.nombre);
            $('#clave').val(section_data.clave);
            $('#model_name').val(section_data.model_name);
            $('#description').val(section_data.description);
            $('#fa_icon').val(section_data.fa_icon);
            $('#orden').val(section_data.orden);

            //
            if (section_data.is_only_admin == 1){
                $('#is_only_admin').attr("checked", true);
            } else {
                $('#is_only_admin').attr("checked", false);
            }
            //
            if (section_data.is_menu_expandible == 1){
                $('#is_menu_expandible').attr("checked", true);
            } else {
                $('#is_menu_expandible').attr("checked", false);
            }

            

        },
        onAddEditReady: function(section_data, opts){

            //
            loadSelectAjax({
                id: "#parent_id",
                url: app.admin_url + "/sys/parent-models",
                parseFields: function(item){
                    return item.nombre + " - " + safeNulValue(item.model_name);
                },
                default_value: ((section_data && section_data.parent_id) ? section_data.parent_id : false),
                prependEmptyOption: true,
                emptyOptionText: "--",
                enable: true
            });

            // def focus
            $("#nombre").focus();
        },
        onGridReady: function(opts){



            //
            $(".btn-order").click(function(e){
                e.preventDefault();

                //
                var record_id = $(this).data("id");
                var new_orden = $(this).data("new_orden");
                //console.log(record_id, value); return;

                //
                $.ajax({
                    type:'POST',
                    url: app.supadmin_url + "/models/update-order",
                    data: $.param({
                        id: record_id,
                        new_orden: new_orden
                    }),
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    success:function(response){
                        //console.log(response.data);
                        if (response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Orden actualizado" });
                            $("#grid_section").DataTable().ajax.reload();
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

            });




        },
        onSectionReady: function(opts){
            //
        }
    });



})(jQuery);