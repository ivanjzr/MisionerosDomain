(function ($) {
    'use strict';



    //
    app.createSection({
        section_title: "Visitors",
        section_title_singular: "Visitor",
        scripts_path: "/app/visitors",
        endpoint_url: app.admin_url + "/visits",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name": "id", "data" : "id"},
                {"name": "address", "data" : "address"},
                {"data" : function(obj){
                        //
                        var country = (obj.country) ? "" + obj.country : "";
                        var state = (obj.state) ? " / " + obj.state : "";
                        var city = (obj.city) ? " / <strong>" + obj.city + "</strong>" : "";
                        return country + state + city;
                    }},
                {"data" : function(obj){
                        return obj.lat + ", " + obj.lng;
                    }},
                {"name": "ip_address", "data" : "ip_address"},
                {"data" : function(obj){
                    return fmtDateEng(obj.visit_datetime.date, true);
                }}
            ],
            columnDefs: [
                { "targets": [0,2,3,4,5],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]],
            lengthMenu: [[50,100,200,500],[50,100,200,500]],
            pageLength: 50
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