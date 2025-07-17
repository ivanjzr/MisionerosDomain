(function ($) {
    'use strict';



    /*
    *
    * SECCION ADMINISTRATORS
    *
    * */
    app.createSection({
        section_title: "Administradores",
        section_title_singular: "Administrador",
        scripts_path: "/sup/administrators",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.supadmin_url + "/administrators",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                        //
                        if (obj.orig_img_url){
                            //
                            var str_info = "<div class='text-center'>";
                            str_info += "<img src='"+obj.orig_img_url + dynurl()+"' class='profile-user-img img-fluid img-circle'>";
                            str_info += "</div>";
                            return str_info;
                        } else {
                            return "";
                        }
                    }},
                {"name": "nombre", "data": "nombre"},
                {"name": "email", "data" : "email"},
                {"data" : function(obj){
                    return obj.phone_cc + " " + obj.phone_number;
                    }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/adm27/administrators/" + obj.id + "/edit";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-info='"+data_info+"'><i class='fas fa-trash'></i></button>";
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
            $('#active').attr("checked", true);


            // def focus
            $("#nombre").focus();
        },
        onGridReady: function(opts){
            /**/
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);