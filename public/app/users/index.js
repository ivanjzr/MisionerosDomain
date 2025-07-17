(function ($) {
    'use strict';



    /*
    *
    * SECCION USERS
    *
    * */
    app.createSection({
        section_title: "Users",
        section_title_singular: "User",
        scripts_path: "/app/users",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/users",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "name", "data" : "name"},
                {"name" : "sys_title", "data" : "sys_title"},
                {"name": "email", "data" : "email"},
                {"data" : function(obj){
                    return obj.phone_cc + " " + obj.phone_number;
                    }},
                {"name": "is_admin", "data" : function(obj){
    
                    // Es administrador
                    if (obj.is_admin){
                        return "<span class='badge bg-success'>Admin</span>";
                    }
                    
                    // Tiene sucursales asignadas
                    else if (obj.sucursales && obj.sucursales.length){
                        var str_sucursales = "";
                        $.each(obj.sucursales, function(idx, item){
                            str_sucursales += "<span class='badge bg-info me-1 mb-1'>" + item.name + "</span>";
                        })
                        return str_sucursales;
                    }
                    
                    // Sin sucursales
                    else {
                        return "<span class='badge bg-danger'>Sin Sucursales</span>";
                    }
                }},
                {"name": "is_pos_user", "data" : function(obj){ return fmtActive3(obj.is_pos_user, true); }},
                {"name": "active", "data" : function(obj){ return fmtActive3(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/users/" + obj.id + "/edit";
                        //
                        //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-view' data-info='"+data_info+"'><i class='fas fa-folder'></i></button> ";
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 4, 6],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
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
            loadSelectAjax({
                id: "#sys_title_id",
                url: app.admin_url + "/sys/titulos/list",
                parseFields: function(item){
                    return item.titulo;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true
            });


            //
            setTimeout(function(){
                //
                $('#password, #password_confirm, #email, #phone_number')
                    .removeAttr("readonly")
            }, 500);


            // def focus
            $("#name").focus();
        },
        onGridReady: function(opts){
            /**/
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);