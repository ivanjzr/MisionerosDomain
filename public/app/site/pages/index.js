(function ($) {
    'use strict';



    //
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/html");




    // or use data: url to handle things like doctype
    function showHTMLInIFrame() {
        //
        //$('#return').html(editor.getValue());
        //
        $('#return').html("<iframe src=" +
            "data:text/html," + encodeURIComponent(editor.getValue()) +
            "></iframe>");
    }
    //
    editor.session.on('change', function (delta) {
        // delta.start, delta.end, delta.lines, delta.action
        showHTMLInIFrame();
    });



    /*
    *
    * SECCION PAGES
    *
    * */
    app.createSection({
        section_title: "Pages",
        section_title_singular: "Page",
        scripts_path: "/app/site/pages",
        endpoint_url: app.admin_url + "/site/pages",
        modalSize: "xl",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "page_name", "data" : "page_name"},
                {"name": "url", "data" : "url"},
                {"name": "header_footer_name", "data" : "header_footer_name"},
                {"data" : function(obj){ return fmtActive(obj.active); }},
                {"data" : function(obj){
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
                    }},
            ],
            columnDefs: [
                { "targets": [0, 5, 6],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            hdrSearch: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(){

            //
            $('#active').attr("checked", true);

        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){


            //
            $('#page_name').val(section_data.page_name);
            $('#url').val(section_data.url);


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
            loadSelectAjax({
                id: "#header_footer_id",
                url: app.admin_url + "/site/header-footer/list",
                parseFields: function(item){
                    return item.header_footer_name;
                },
                default_value: ( (section_data && section_data.header_footer_id) ? section_data.header_footer_id : null ),
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true
            });



            /*
            $('#body').summernote({
                placeholder: '',
                height: 150,
                callbacks: {
                    onInit: function() {
                        $("#body").summernote("code", ( (section_data && section_data.body) ? section_data.body : ""));
                    }
                }
            });
             */


            // def focus
            $("#page_name").focus();
        },



        /*
        *
        * VAMOS A USAR POST INDIVIDUAL DEBIDO A QUE PUEDE HABER INPUTS DENTRO DE LA PAGINA Y AL USAR
        * POST SOLO ENVIAMOS LOS CAMPOS NECESARIOS
        *
        * Si queremos usar post individual entonces utilizamos usePost y beforeSubmit
        * tambien si queremos hacer bypass de la validacion lo hacemos
        *
        * */
        usePost: true,
        btnSave: ".btnAdd2",
        setPostData: function(){
            return {
                page_name: $("#page_name").val(),
                url: $("#url").val(),
                header_footer_id: $("#header_footer_id").val(),
                //body: $("#body").summernote("code"),
                active: ( $("#active").is(":checked") ? 1 : "" ),
            }
        },
        beforeSubmit: function(arr, append_url){

        },
        onGridReady: function(opts){
            //
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);