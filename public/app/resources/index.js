(function ($) {
    'use strict';



    

    //
    app.createSection({
        section_title: "Recursos",
        section_title_singular: "Recurso",
        scripts_path: "/app/resources",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/appointments/resources",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "resource_type_str", "data" : "resource_type_str"},
                {"name": "active", "data" : function(obj){ 
                    if (obj.resource_type === "m"){
                        return obj.name;
                    } else if (obj.resource_type === "h"){
                        return obj.name + " <small>(" + obj.employee_email + ")</small>";
                    } else {
                        return "";
                    }
                }},
                {"name" : "location_name", "data" : "location_name"},
                {"name": "active", "data" : function(obj){
                    //
                    var horario_url = "/admin/working-hours/" + obj.working_hours_id + "/edit";
                    return " <a href='"+horario_url+"' class='btn btn-sm btn-flat btn-info'> " + obj.working_hours_name + " <i class='fas fa-arrow-right'></i></a>";
                }},
                {"name" : "exceptions_count", "data" : "exceptions_count"},
                {"name" : "services_count", "data" : "services_count"},                
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        //var newObject = jQuery.extend(true, {}, obj); newObject.description = null;
                        var data_info = JSON.stringify({});
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/appointments/resources/" + obj.id + "/edit";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
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
            deferLoading: true,
            hdrBtnsSearch: true,
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){

            //
            $('#active').attr("checked", true);

        
            //
            loadSelectAjax({
                id: "#location_id",
                url: app.admin_url + "/locations/list-available",
                parseFields: function(item){
                    return item.name;
                },
                saveValue: true,
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true,
            });


            //
            loadSelectAjax({
                id: "#working_hours_id",
                url: app.admin_url + "/working-hours/list-available",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                saveValue: true,
                enable: true,
            });


            //
            loadSelectAjax({
                id: "#employee_id",
                url: app.admin_url + "/employees/list-available",
                parseFields: function(item){
                    return item.name + " (" + item.job_title + ")";
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true,
            });

            function loadResTypeContainer(){
                //
                $("#name_container").hide();
                $("#employee_container").hide();
                //
                let resource_type = $("#resource_type").val();
                //
                if (resource_type==="h"){
                    $("#employee_container").show();
                } else {
                    $("#name_container").show();
                }
            }
            
            //
            selectLoad2({
                id: "#resource_type",
                data: [
                    {id:"m", name:"Instalacion"},
                    {id:"h", name:"Empleado"},
                ],
                emptyOptionText: "--select",
                enable: true,
                saveValue: true,
                onChange: function(value) {
                    //console.log('onChange:', value);
                    loadResTypeContainer();
                },
                onReady: function(value, items) {
                    //console.log('onReady:', value, items);
                    loadResTypeContainer();
                }
            });

            //$(".sucursal_name").text($("#sucursal_name").val());


            // def focus
            $("#name").focus();
        },
        beforeSubmit: function(arr){
            //
            var sel_employee = $('#employee_id option:selected').data("info");
            let resource_type = $("#resource_type").val();
            //
            let nameField = arr.find(item => item.name === "name");
            //
            if (resource_type === "h"){
                nameField.value = sel_employee.name;
            }
        },
        onGridReady: function(opts){

            //
            $(".btn-view-download-file").click(function(e){
                e.preventDefault();
                //
                var data_info = $(this).data("info");
                //
                loadModalV2({
                    id: "modal-view-download-image",
                    modal_size: "lg",
                    html_tmpl_url: "/app/common/preview-img.html?v=3",
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();
                        //
                        $('#modal-title').text("Imagen " + data_info.nombre);
                        $("#preview_img_id").attr("src", data_info.orig_img_url);
                    }
                });

            });

        },
        onSectionReady: function(opts){


            //
            $("#filter_resource_type").change(function(){
                filterGrid();
            });


            // 
            loadSelectAjax({
                id: "#filter_location_id",
                url: app.admin_url + "/locations/list",
                parseFields: function(item){
                    return item.name;
                },
                prependEmptyOption: true,
                emptyOptionText: "--todas",
                saveValue: true,
                enable: true,
                onChange: function(){
                    filterGrid()
                },
                onReady: function(){
                    filterGrid()
                }
            });


            //
            $(".resouce_name").text($("#name").val());
        }
    });



    //
    function filterGrid(){
        //
        var filter_location_id = $("#filter_location_id").val();
        var filter_resource_type = $("#filter_resource_type").val();
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/appointments/resources?lid=" + filter_location_id + "&res-type=" + filter_resource_type);
        $("#grid_section").DataTable().ajax.reload();
    }


    //filterGrid();



    
    



})(jQuery);