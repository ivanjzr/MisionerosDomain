define(function(){
    function moduleReady(modal, section_data){
        console.log(modal, section_data);



        //
        $('#modal-title').text("Dismissed messages for " + section_data.title);


        //
        var field_name = null;

        if ( section_data.send_type === "c" ){
            $("#field_title").text("Customer Name");
            field_name = "customer_name";
        } else if ( section_data.send_type === "s" ){
            $("#field_title").text("Store Name");
            field_name = "store_name";
        }

        //
        dataGrid({
            gridId: "#grid_notifications_dismissed",
            url: app.admin_url + "/notifications/" + section_data.id + "/dismissed",
            columns: [
                {"name" : "id", "data" : "id"},
                {"name": field_name, "data" : field_name},
                {"data" : function(obj){ return fmtDateEng(obj.datetime_created.date);}},
            ],
            columnDefs: [
                {
                    "targets": "_all",
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            order: [[ 0, "desc" ]],
            gridReady: function(){
                //
            }
        });





    }
    return {init: moduleReady}
});






