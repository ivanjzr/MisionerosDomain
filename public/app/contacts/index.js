(function ($) {
    'use strict';



    //
    app.createSection({
        section_title: "Contactos",
        section_title_singular: "Contacto",
        scripts_path: "/app/contacts",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/contacts",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "name", "data" : "name"},
                {"name": "email", "data" : "email"},
                {"data" : function(obj){
                    return obj.phone_cc + " " + obj.phone_number;
                    }},
                {"data" : function(obj){
                    if (obj.has_employee){
                        return "Employee ID #" + obj.employee_id + " " + fmtActive(obj.employee_active);
                    }
                    return "--";
                }},
                {"data" : function(obj){
                    if (obj.has_user){
                        return "User ID #" + obj.user_id + " " + fmtActive(obj.user_active);
                    }
                    return "--";
                }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/contacts/" + obj.id + "/edit";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            deferLoading: true,
            columnDefs: [
                { "targets": [0,2,3,4,5],"orderable": false },
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
                enable: true,
            });


            //
            setTimeout(function(){
                //
                $('#email, #phone_number')
                    .removeAttr("readonly")
            }, 500);


            // def focus
            $("#name").focus();
        },
        onGridReady: function(opts){
            
            
            
            
        },
        onSectionReady: function(opts){
            
            
          

        }
    });



    //
    function filterGrid(){
        //
        var filter_tipo = $("#filter_tipo").val();
        let str_qs = (filter_tipo) ? "?ft=" + filter_tipo : "";
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/contacts" + str_qs);
        $("#grid_section").DataTable().ajax.reload();
    }

    

    //
    $("#filter_tipo").change(function(){
        filterGrid();
    });

    setTimeout(function(){
        filterGrid();
    }, 1000);
    


})(jQuery);