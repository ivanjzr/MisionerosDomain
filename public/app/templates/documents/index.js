(function ($) {
    'use strict';



    /*
    *
    * SECCION MAQUETAS DE Documentos
    *
    * */
    app.createSection({
        section_title: "Documentos",
        section_title_singular: "Documento",
        editFieldName: "maqueta_name",
        scripts_path: "/app/templates/documents",
        endpoint_url: app.admin_url + "/templates-documents",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {visible: false, "name": "company_name", "data" : "company_name"},
                {"name": "maqueta_name", "data" : "maqueta_name"},
                {"data" : function(obj){ return fmtActive(obj.active); }},
                {"data" : function(obj){ return fmtActive(obj.in_use); }},
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
                { "targets": [0, 3, 4, 5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            hdrSearch: true,
            deferLoading: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(){


            //
            $('#active').attr("checked", true);
            $('#in_use').attr("checked", true);


        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){


            //
            $('#account_id').val(section_data.account_id);


            //
            $('#maqueta_name').val(section_data.maqueta_name);
            $('#maqueta_content').val(section_data.maqueta_content);
            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }
            //
            if (section_data.in_use){
                $('#in_use').attr("checked", true);
            } else {
                $('#in_use').attr("checked", false);
            }



           
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){

            //
            $('#maqueta_content').summernote({
                placeholder: '',
                height: 150
            });

            // def focus
            $("#maqueta_name").focus();


            //
            var filter_tipo_documento_id = $("#filter_tipo_documento_id").val();
             //
             loadSelectAjax({
                id: "#tipo_documento_id",
                url: app.admin_url + "/sys/maquetas/tipos-documentos",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                default_value: ((section_data && section_data.tipo_documento_id) ? section_data.tipo_documento_id : filter_tipo_documento_id),
                emptyOptionText: "--select",
                enable: true
            });

        },
        onGridReady: function(opts){


            //
            var filter_tipo_documento_id = $("#filter_tipo_documento_id").val();
            if (filter_tipo_documento_id){
                //
                $("#maqueta_info")
                    .html("")
                    .removeClass("text-danger");
                //
                $.ajax({
                    type:'GET',
                    url: app.admin_url + "/templates-documents/" + filter_tipo_documento_id + "/maqueta-info",
                    success:function(section_data){
                        //
                        if (section_data.error){
                            app.Toast.fire({ icon: 'error', title: section_data.error });
                        }
                        //
                        else {
                            //
                            if ( !section_data.maqueta_has_docs ){
                                //
                                $("#maqueta_info")
                                    .html("<i class='fas fa-info-circle'></i> La maqueta no tiene un documento habilitado")
                                    .addClass("text-danger");
                            }
                        }
                    },
                    error: function(){
                        //
                        enable_btns();
                        preload(false);
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });
            }



        },
        onSectionReady: function(opts){



            //
            function filterGrid(){
                //
                var filter_tipo_documento_id = $("#filter_tipo_documento_id").val();
                // 
                $("#grid_section").DataTable().ajax.url(app.admin_url + "/templates-documents?filter_tipo_documento_id=" + filter_tipo_documento_id);
                $("#grid_section").DataTable().ajax.reload();
            }


            //
            loadSelectAjax({
                id: "#filter_tipo_documento_id",
                url: app.admin_url + "/sys/maquetas/tipos-documentos",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                saveValue: true,
                enable: true,
                onChange: function(){
                    filterGrid()
                },
                onReady: function(){
                    filterGrid()
                }
            });

        }
    });



})(jQuery);