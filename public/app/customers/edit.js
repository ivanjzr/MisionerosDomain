(function ($) {
    'use strict';


    //
    const section_name = $("#section_name").val();

    //
    var defaultBirthDate = moment().subtract(35, 'years');
    //
    $('#birth_date').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es-mx',
        date: defaultBirthDate,
        buttons: {
            showToday: false,
            showClear: false,
            showClose: false
        },
        icons: app.tdBs5Icons,
        viewMode: 'years', 
            minViewMode: 'days',
        pickTime: false,
        calendarWeeks: false,
        showTodayButton: false
    });




    

    //
    function onEditReady(section_data, opts){

        //
        $(".section-title").html(section_data.name);
        //
        $('#name').val(section_data.name);
        $('#username').val(section_data.username);
        $('#email').val(section_data.email);
        $('#home_address').val(section_data.home_address);
        $('#phone_cc').val(section_data.phone_cc);
        $('#phone_number').val(section_data.phone_number);
        $('#notes').val(section_data.notes);
        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }

        //
        setTimeout(function(){
            const birth_date = moment(section_data.birth_date.date);
            $('#birth_date').datetimepicker('date', birth_date);
        }, 500);
        

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


        //
        loadSelectAjax({
            id: "#phone_country_id",
            url: app.public_url + "/paises/list",
            parseFields: function(item){
                return "+" + item.phone_cc + " (" + item.abreviado + ")";
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: ((section_data && section_data.phone_country_id) ? section_data.phone_country_id : app.ID_PAIS_EU),
            enable: true
        });


        //
        $("#modal-cust-title").html("Editar " + section_name + " " + section_data.name);        


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
            js_url: "/app/customers/modules/familiares.js",
            onInit: function(){
                enable_btns();
            }
        });


        
    }




    //
    app.createSection({
        section_title: section_name,
        section_title_singular: section_name,
        scripts_path: "/app/customers",
        endpoint_url: app.admin_url + "/customers",
        record_id: record_id,
        onEditReady: onEditReady,
        loadModules: loadModules,
        //
        beforeSubmit: function(arr){

            //
            arr.push({
                name: "birth_date",
                value: $('#birth_date').datetimepicker('date').format('YYYY-MM-DD')
            });
            
        },
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