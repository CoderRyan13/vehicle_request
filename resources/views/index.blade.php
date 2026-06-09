<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Request</title>
    <meta name="csrf-token" content="{{csrf_token ()}}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
</head>
<body class="bg-black">
    <div class="d-flex align-items-center justify-content-center flex-column" style="height: 95vh;">
        
        <img src="westrac_white.png" alt="westrac" class="img-fluid mb-4">
        
        <div class="card custom-card col-lg-4 col-md-6 col-sm-10 px-4 py-3">
            <h3 class="text-center text-primary">Request Vehicle Form</h3>
            <div>
                <div class="mt-2">
                    <label>Requested By</label>
                    <select name="supervisor" class="form-control supervisor" required></select>
                </div>
                <div class="mt-2">
                    <label>Time OUT Date</label>
                    <input type="text" class="form-control timeout-date" id="datetime" name="timeout_date" value="<?php date_default_timezone_set("America/Belize"); echo date('Y-m-d H:i:s') ?>" required>
                </div>
                <div class="mt-2">
                    <label>Place of Destination</label>
                    <input type="text" class="form-control destination" name="destination" required>
                </div>
                <div class="mt-2">
                    <label>Purpose of Trip</label>
                    <input type="text" class="form-control purpose" name="purpose" required>
                </div>
                <div class="mt-2">
                    <label>Preferred Vehicle (Optional)</label>
                    <input type="text" class="form-control preferred-vehicle" name="preferred_vehicle">
                </div>
                <div class="mt-2">
                    <label>Driver</label>
                    <select name="driver" class="form-control driver" required></select>
                </div>
                <button class="btn btn-success mt-4 form-control submit">Submit</button>
            </div>
        </div>
    </div>
    <span class="d-flex justify-content-end"><a href="{{ url('/admin') }}" target="_blank" class="text-decoration-none text-black me-2 fs-4">Admin</a></span>

    <div class="container-fluid bg-white ms-5" style="width: 95%;">
        <h2 class="text-primary">Calendar</h2>

        <div id='calendar'></div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="toastAlert" class="toast colored-toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toastHead toast-header text-fixed-white">
                <img class="bd-placeholder-img rounded me-2" src="{{url('/')}}/westrac_icon.png" alt="..." style="width: 20px;">
                <strong class="me-auto toastAlertTitle text-white"></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
            <div class="toast-body toastAlertBody text-fixed-white"></div>
        </div>
    </div>
</body>
<script>
    let display_alert = (title, text, class_name) => {
        if (0 == class_name) {
            class_name = 'bg-danger-subtle';
            class_head = 'bg-danger';
        }
        else if (1 == class_name) {
            class_name = 'bg-success-subtle';
            class_head = 'bg-success';
        }
        else if (2 == class_name) {
            class_name = 'bg-info-subtle';
            class_head = 'bg-info';
        }
        else if (3 == class_name) {
            class_name = 'bg-warning-subtle';
            class_head = 'bg-warning';
        }

        $('#toastAlert').addClass(class_name);
        $('.toastHead').addClass(class_head);
        $('.toastAlertTitle').html(title);
        $('.toastAlertBody').html(text);

        const _toast = document.getElementById('toastAlert')
        const toast = new bootstrap.Toast(_toast);
        toast.show();
    }

    let cal = [];

    $(document)
        .ready(function(e) {
            $.ajax({
				type		: 'POST',
				url		: "{{url ('/')}}/get_approved",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
				dataType	: 'json',
				success	: function (data) {
                    $.each(data, function(key, val) {
                        let veh_num = val.approved_vehicle.slice(-3);
                        cal.push({ title: `${val.driver} ( ${veh_num} )`, start: `${val.timeout_date}` },);
                    });

                    var calendarEl = document.getElementById('calendar');
                    var calendar = new FullCalendar.Calendar(calendarEl, { 
                        initialView: 'dayGridMonth' ,
                        events: cal,
                    });
                    calendar.render();
				},
				error		: function (request, status, error) {
					console.log (request.status, request.responseText);
				},
				async		: false
			});

            flatpickr("#date", {});
            flatpickr("#datetime", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
            });

            $.ajax({
				type		: 'POST',
				url		: "{{url ('/')}}/get_all_drivers",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
				dataType	: 'json',
				success	: function (data) {
                    let finalHTML = '<option value="">-</option>';

                    $.each(data, function(key, val) {
                        finalHTML += ` <option value="${val.driver}">${val.driver}</option> `;
                    });

                    $(".driver").append(finalHTML);
				},
				error		: function (request, status, error) {
					console.log (request.status, request.responseText);
				},
				async		: false
			});

            $.ajax({
				type		: 'POST',
				url		: "{{url ('/')}}/get_all_supervisors",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
				dataType	: 'json',
				success	: function (data) {
                    let finalHTML = '<option value="">-</option>';

                    $.each(data, function(key, val) {
                        finalHTML += ` <option value="${val.supervisor}">${val.supervisor}</option> `;
                    });

                    $(".supervisor").append(finalHTML);
				},
				error		: function (request, status, error) {
					console.log (request.status, request.responseText);
				},
				async		: false
			});

            $('.driver').select2();
            $('.supervisor').select2();
        })
        .on('click', '.submit', function(e) {
            $('.submit').prop( "disabled", true);
            $.ajax({
				type		: 'POST',
				url		: "{{url ('/')}}/request_vehicle",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
				dataType	: 'json',
                data : {
                    'supervisor'        : $('.supervisor').val(),
                    'timeout_date'      : $('.timeout-date').val(),
                    'destination'       : $('.destination').val(),
                    'purpose'           : $('.purpose').val(),
                    'preferred_vehicle' : $('.preferred-vehicle').val(),
                    'driver'            : $('.driver').val(),
                },
				success	: function (data) {
                    if (data.trim() == 'f') {
                        display_alert ('Request Vehicle', 'All mandatory fields required.', 3);
                        $('.submit').prop( "disabled", false);
                        // setTimeout("location.href = '/';",2500);
                    } else if (data.trim() == 'p') {
                        display_alert ('Request Vehicle', 'Cannot use a date and time that has already passed to request a vehicle.', 3);
                        $('.submit').prop( "disabled", false);
                        // setTimeout("location.href = '/';",2500);
                    } else if (data.trim() == 's') {
                        display_alert ('Request Vehicle', 'Your vehicle was requested successfully.', 1);
                        setTimeout("location.href = '/';",3000);
                    } else if (data.trim() == 'e') {
                        display_alert ('Request Vehicle', 'Your vehicle could not be requested at this moment.', 0);
                        setTimeout("location.href = '/';",3000);
                    }
				},
				error		: function (request, status, error) {
					console.log (request.status, request.responseText);
				},
				async		: false
			});
        })
</script>
</html>