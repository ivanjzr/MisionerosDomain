(function ($) {
    'use strict';



    /*
    *
    * SECCION CUENTAS
    *
    * */
    app.createSection({
        section_title: "Cuentas",
        section_title_singular: "Cuenta",
        scripts_path: "/sup/accounts",
        endpoint_url: app.supadmin_url + "/accounts",
        editFieldName: "company_name",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "company_name", "data" : "company_name"},
                {"name": "contact_name", "data" : "contact_name"},
                {"name": "notes", "data" : "notes"},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/adm27/accounts/" + obj.id + "/edit";
                        var models_url = "/adm27/accounts/" + obj.id + "/models/index";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-primary btn-flat' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <a href='"+models_url+"' class='btn btn-sm btn-info btn-flat' data-info='"+data_info+"'><i class='fas fa-list-alt'></i> Modelos </a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-info='"+data_info+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){
            //
            $('#active').attr("checked", true);
        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){

            //
            $("#company_name").val(section_data.company_name);
            $('#contact_name').val(section_data.contact_name);

            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }

        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){
            //
            $("#company_name").focus();
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);