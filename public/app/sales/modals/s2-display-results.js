define(function(){
    function moduleReady(modal, section_data){
        //console.log("------display_results: ", section_data);


        //
        var results = section_data.results;






        //
        $("#tbl_body_results").html("");


        //
        if (results && results.length){
            
            //
            $.each(results, function(idx, item){
                //console.log(item);
                //
                var item_el = app.addItem(item, true);
                $("#tbl_body_results").append(item_el);
            });
            
             //
             $(".btn-select-item").click(function(e) {
                e.preventDefault();

                //
                var selected_item = $(this).data("selected-item");
                //console.log(selected_item); return;
                //
                $("#modal-display-results").find('.modal').modal("hide");
                $(document).trigger(section_data.bindFuncName, selected_item);

            });

        }



    }
    return {init: moduleReady}
});