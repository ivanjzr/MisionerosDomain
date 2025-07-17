define(function(){
    function moduleReady(section_data){
        console.log(section_data);



    /*
    *
    * SECCION CONFIG
    *
    * */
    app.createSection({
        gridId: "#grid_layouts",
        formName: "#form_layouts",
        section_title: "Layout",
        btnAddRecord: "#btnAddLayout",
        btnReloadGrid: "#btnReloadLayouts",
        section_title_singular: "Layout",
        editFieldName: "layout_name",
        scripts_path: "/app/site/config/layouts",
        endpoint_url: app.admin_url + "/site/layouts",
        modalSize: "xl",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "layout_name", "data" : "layout_name"},
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
                { "targets": [0, 3],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){

            //
            var layout_name = (section_data && section_data.layout_name) ? section_data.layout_name : null;
            //
            $("#layout_name").val(layout_name);

            //
            $('#layout_content').summernote({
                placeholder: '',
                height: 150,
                callbacks: {
                    onInit: function() {
                        //
                        $('#layout_content').summernote('codeview.activate');
                        $("#layout_content").summernote("code", ((section_data && section_data.layout_content) ? section_data.layout_content : ""));
                        //
                        $("#layout_name").focus();
                    }
                }
            });


        },
        beforeSubmit: function(arr){
            //
            arr.push({
                name: "layout_content",
                value: $("#layout_content").summernote('code')
            });
        },
        onGridReady: function(opts){
            //
        },
        onSectionReady: function(opts){
            //
        }
    });




    }
    return {init: moduleReady}
});