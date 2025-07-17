define(function(){
    function moduleReady(modal, filter_info){
        //console.log(filter_info);


        //
        var str_info = "Starting At: <strong>" + filter_info.staring_point.name + "</strong>" + " - " + filter_info.staring_point.address;
        str_info += "&emsp; Ending At: <strong>" + filter_info.ending_point.name + "</strong>" + " - " + filter_info.ending_point.address;
        //
        $('#starting_point_address').html(str_info);





        //
        app.directionsDisplay;
        app.directionsService = new google.maps.DirectionsService();



        //
        const map = new google.maps.Map(document.getElementById("map"), {
            disableDefaultUI: true,
            center: { lat: 31.761877, lng: -106.485023 },
            zoom: 13,
        });




        //
        app.directionsDisplay = new google.maps.DirectionsRenderer({
            suppressMarkers: true
        });
        //
        app.directionsDisplay.setMap(map);



        //
        app.markers = [];
        app.flightPlanCoordinates = [];




        //
        function addMarker(item_status, lat, lng, str_header, str_content, address){

            //
            var icon = {
                scaledSize: new google.maps.Size(50, 50), // scaled size
                origin: new google.maps.Point(0,0), // origin
                anchor: new google.maps.Point(0, 0) // anchor
            }

            //
            if ( item_status==='ready' ){
                icon.url = "http://maps.google.com/mapfiles/ms/icons/green-dot.png";
            }
            //
            else if ( item_status==='starting_point' || item_status==='ending_point' ){
                icon.url = "http://maps.google.com/mapfiles/ms/icons/blue-dot.png";
            }
            //
            else {
                icon.url = "/adm/img/gray-dot.png";
            }


            var icon2 = {
                path: "M-20,0a20,20 0 1,0 40,0a20,20 0 1,0 -40,0",
                fillColor: '#FFF',
                fillOpacity: 1.0,
                anchor: new google.maps.Point(0,0),
                strokeWeight: 2,
                scale: 1
            }

            //
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng( lat, lng),
                map: map,
                title: str_header,
                icon: icon,
                //label: "#123"
            });

            //
            //marker.setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png')


            //
            var contentString = "<div>";
            contentString += "<div style='font-size:14px;font-weight: bold;'>"+str_header+"</div>";
            contentString += "<div>"+removeEPEUWords(str_content)+"</div>";
            /*
            * todo - esta funcion se debe de mandar llamar desde el inicio en un script
            * */
            if (item_status === "ready"){
                contentString += "<div style='text-align: center;'>";
                contentString += '<button onclick="openDestination(\'' + address + '\')" style="margin: 6px 0 0 0; background-color: green; color: yellow; border: none;padding: 4px;"> View Directions </button>';
                contentString += "</div>";
            }
            contentString += "</div>";




            //
            var infowindow = new google.maps.InfoWindow({
                content: " "
            });



            //
            google.maps.event.addListener(marker, 'click', function() {
                //
                infowindow.close();
                //marker.setVisible(false);
                //
                infowindow.open(map,marker);
                //console.log(marker);
                //
                infowindow.setContent(contentString);
            });

            // google.maps.event.trigger(marker, 'click');




            // To add the marker to the map, call setMap();
            marker.setMap(map);


            //
            app.flightPlanCoordinates.push(marker.getPosition());

            //
            app.markers.push(marker);
        }





        //
        function removeEPEUWords(str_text){
            return str_text.replace("El Paso, TX", "").trim().replace(", EE. UU.", "").trim();
        }



        function myNavFunc(lat, lng){

            var link = "https://www.google.com/maps/dir/?api=1&dir_action=navigate&destination="+lat+","+lng;
            window.open(link);
            return;

            // If it's an iPhone..
            if ((navigator.platform.indexOf("iPhone") != -1)
                || (navigator.platform.indexOf("iPod") != -1)
                || (navigator.platform.indexOf("iPad") != -1)) {
                //
                link = "maps://www.google.com/maps/dir/?api=1&dir_action=navigate&destination=" + lat + "," + lng;
                console.log(link);
                window.open(link);
            }
            //
            else {
                link = "https://www.google.com/maps/dir/?api=1&dir_action=navigate&destination="+lat+","+lng;
                console.log(link);
                window.open(link);
            }


        }






        //
        addMarker('starting_point', filter_info.staring_point.lat, filter_info.staring_point.lng, "Starging Point", filter_info.staring_point.name + " - " + filter_info.staring_point.address);




        //
        function onDataReady(arr_orders){
            // Zoom and center map automatically by arr_orders (each station will be in visible map area)
            var lngs = arr_orders.map(function(station) { return station.lng; });
            var lats = arr_orders.map(function(station) { return station.lat; });
            map.fitBounds({
                west: Math.min.apply(null, lngs),
                east: Math.max.apply(null, lngs),
                north: Math.min.apply(null, lats),
                south: Math.max.apply(null, lats),
            });

            // Show arr_orders on the map as markers
            for (var i = 0; i < arr_orders.length; i++) {
                new google.maps.Marker({
                    position: arr_orders[i],
                    map: map,
                    title: arr_orders[i].name
                });
            }

            // Divide route to several parts because max arr_orders limit is 25 (23 waypoints + 1 origin + 1 destination)
            for (var i = 0, parts = [], max = 25 - 1; i < arr_orders.length; i = i + max)
                parts.push(arr_orders.slice(i, i + max + 1));



            // Service callback to process service results
            var service_callback = function(response, status) {


                if (status != 'OK') {
                    console.log('Directions request failed due to ' + status);
                    return;
                }
                var renderer = new google.maps.DirectionsRenderer;
                renderer.setMap(map);
                renderer.setOptions({ suppressMarkers: true, preserveViewport: true });
                renderer.setDirections(response);

            };

            // Send requests to service to get route (for arr_orders count <= 25 only one request will be sent)
            for (var i = 0; i < parts.length; i++) {


                console.log(parts);
                //addMarker(item.sale_status, item.lat, item.lng, "Order# " + item.id + " - " + item.customer_name, item.address + " <small>(" + app.getDistanceMiles(item.distance_meters) + " mi)</small>", item.address);



                // Waypoints does not include first station (origin) and last station (destination)
                var waypoints = [];
                for (var j = 1; j < parts[i].length - 1; j++)
                    waypoints.push({location: parts[i][j], stopover: false});


                // Service options
                var service_options = {
                    origin: parts[i][0],
                    destination: parts[i][parts[i].length - 1],
                    waypoints: waypoints,
                    optimizeWaypoints: true,
                    travelMode: google.maps.TravelMode.DRIVING
                };


                // Send request
                app.directionsService.route(service_options, function (response, status) {
                    //
                    if (status == google.maps.DirectionsStatus.OK) {
                        app.directionsDisplay.setDirections(response);
                        var route = response.routes[0];

                        //
                        var summaryPanel = '';

                        // For each route, display summary information.
                        for (var i = 0; i < route.legs.length; i++) {
                            var routeSegment = i + 1;
                            //

                            /*
                            var str_order_customer_info = "";
                            //
                            $.each(arr_items, function(idx, item){
                                if ( i === idx ){
                                    //str_order_customer_info = " " + item.id + " - " + item.customer_name;
                                }
                            });
                             */
                            console.log(route.legs[i]);

                            //
                            var start_address = route.legs[i].start_address;
                            var end_address = route.legs[i].end_address;


                            //
                            const query_string_end_address = encodeURIComponent(end_address).replace('%20','+');
                            var destination_link = "https://www.google.com/maps/dir/?api=1&dir_action=navigate&destination="+query_string_end_address;
                            //console.log(destination_link);
                            //
                            summaryPanel += "<br /><a href='"+destination_link+"' target='_blank'><span class='fa fa-directions' style='font-size:20px;color:orangered;'></span>&nbsp;<strong>Route Segment: " + routeSegment + "</strong> - ";
                            summaryPanel += removeEPEUWords(start_address) + " to ";
                            summaryPanel += removeEPEUWords(end_address) + " ";
                            summaryPanel += "<small>(" + route.legs[i].distance.text + ")</small></a>";
                        }

                        //
                        $('#route_segment').html("Optimizated Routes: <strong>" + summaryPanel);
                    }
                });
            }
        }







        //
        get({
            url: app.admin_url + "/deliveries/map-list?filter_week_date=" + filter_info.week_date + "&staring_point_store_id="+filter_info.staring_point.id,
            //authToken: app.auth_user.token,
            success: function(response){
                //
                if (response && response.length){


                    onDataReady(response);

                    /*
                    //
                    var arr_items = [];

                    //
                    $.each(response, function(idx, item){
                        addMarker(item.sale_status, item.lat, item.lng, "Order# " + item.id + " - " + item.customer_name, item.address + " <small>(" + app.getDistanceMiles(item.distance_meters) + " mi)</small>", item.address);
                        arr_items.push(item);
                    });
                    //
                    addMarker('ending_point', filter_info.ending_point.lat, filter_info.ending_point.lng, "Ending Point", filter_info.ending_point.name + " - " + filter_info.ending_point.address);
                    //
                    var bounds = new google.maps.LatLngBounds();
                    //
                    for (var i=0; i < app.markers.length; i++) {
                        if( app.markers[i].getVisible() ) {
                            bounds.extend( app.markers[i].getPosition() );
                        }
                    }
                    map.fitBounds(bounds);
                    //
                    var start = app.flightPlanCoordinates[0];
                    var end = app.flightPlanCoordinates[app.flightPlanCoordinates.length - 1];
                    var waypts = [];
                    for (var i = 1; i < app.flightPlanCoordinates.length - 1; i++) {
                        waypts.push({
                            location: app.flightPlanCoordinates[i],
                            stopover: true
                        });
                    }
                    calcRoute(start, end, waypts, arr_items);
                     */

                }
            },
            error: function(){
                alert("Network Error");
            }
        });





    }
    return {init: moduleReady}
});






