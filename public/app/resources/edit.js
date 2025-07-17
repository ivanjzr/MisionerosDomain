(function ($) {
    'use strict';




    //
    function onEditReady(section_data, opts){

        //
        $('#name').val(section_data.name);
        $('#description').val(section_data.description);


        var str_res_type = "";
        if (section_data.resource_type == "m"){
            str_res_type = "Instalacion";
        } else if (section_data.resource_type == "h"){
            str_res_type = "Empleado - " + section_data.employee_name + " (" + section_data.employee_email + ")";
        }
        $('#resource_type').val(str_res_type);

        //
        $('#location_name').val(section_data.location_name);
        $('.resource-title').text(section_data.name);

        


        //
        loadSelectAjax({
            id: "#working_hours_id",
            url: app.admin_url + "/working-hours/list-available",
            parseFields: function(item){
                return item.nombre;
            },
            prependEmptyOption: true,
            default_value: section_data.working_hours_id,
            emptyOptionText: "--select",
            saveValue: true,
            enable: true,
        });



        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }

        



        // def focus
        $("#name").focus();

    }







    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;


        //
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/resources/modules/services.js",
            onInit: function(){
                enable_btns();
            }
        });


        

        //
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/resources/modules/schedule_exceptions.js",
            onInit: function(){
                enable_btns();
            }
        });

        

    }




    //
    app.createSection({
        section_title: "Recursos",
        section_title_singular: "Recurso",
        scripts_path: "/app/resources",
        endpoint_url: app.admin_url + "/appointments/resources",
        record_id: record_id,
        onEditReady: onEditReady,
        loadModules: loadModules,
        onSectionReady: function(opts){

            //
            $('#btnReloadDetails').click(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                opts.loadData();
            });

        }
    });




})(jQuery);