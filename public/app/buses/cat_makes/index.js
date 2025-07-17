(function ($) {
    'use strict';



    //
    app.createSection({
        section_title: "Makes",
        section_title_singular: "Make",
        scripts_path: "/app/buses/cat_makes",
        endpoint_url: app.admin_url + "/buses/makes/",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "nombre", "data" : "nombre"},
                {"data" : function(obj){ return safeNulValue(obj.description); }},
                {"name": "active", "data" : function(obj){ return fmtActive(obj.active); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-view' data-info='"+data_info+"'><i class='fas fa-folder'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
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
            $('#active').attr("checked", true);
        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){

            //
            $("#nombre").val(section_data.nombre);
            $("#fa_icon").val(section_data.fa_icon);
            $('#description').val(section_data.description);
            $('#url').val(section_data.url);

            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }

            //
            if ( section_data.thumb_img_url ){
                //
                $('#img_section_url').attr("src", section_data.thumb_img_url + dynurl());
                $('#img_section_url').attr("data-id", section_data.id);
                $('#img_section_url').css({
                    "width":200,
                    "height":150
                });
                $('#img_section_container').show();
            } else {
                $('#img_section_url').attr("src", null);
                $('#img_section_container').hide();
            }
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){
            //
            $("#nombre").keyup(function(e) {
                //
                var nombre = $(this).val();
                $("#url").val(convertToUrl(nombre));
            });
            // def focus
            $("#nombre").focus();
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);