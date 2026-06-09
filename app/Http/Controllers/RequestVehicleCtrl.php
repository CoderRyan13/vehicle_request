<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; 
use DB;

class RequestVehicleCtrl extends Controller
{
    public function request_vehicle(Request $request) {
        date_default_timezone_set("America/Belize");

        if($request->supervisor == NULL || $request->timeout_date == NULL || $request->destination == NULL || $request->purpose == NULL || $request->driver == NULL) { return json_encode('f'); }

        if (date('Y-m-d H:i:s', strtotime ($request->timeout_date . '+60 minutes')) < date('Y-m-d H:i:s')) { return json_encode('p'); }

        $fields = [
            'supervisor'        => $request->supervisor,
            'timeout_date'      => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
            'destination'       => $request->destination,
            'purpose'           => $request->purpose,
            'preferred_vehicle' => $request->preferred_vehicle,
            'driver'            => $request->driver,
        ];

        if (DB::table('public.vehicle_request')->insert($fields)) {
            $employee = $request->driver;
            $data = ['name' => 'Mr. Heinrich Wiebe', 'supervisor' => $request->supervisor, 'employee' => $request->driver];

            Mail::send('mail.email', $data, function($message) {
                $message->to('henrywiebe@westracbelize.com', 'Fleet Coordinator')->subject("New Vehicle Request");
                $message->from('henrywiebe@westracbelize.com', 'Westrac Ltd Vehicle Request');
            });

            return json_encode('s');
        } else {
            return json_encode('e');
        }
    }

    public function get_request(Request $request) {
        $arg = [
            'id' => trim($request->id)
        ];
        $veh_req = DB::select("SELECT * FROM public.vehicle_request WHERE is_approved = false AND is_denied = false AND id = :id ORDER BY id ASC", $arg);
        return json_encode($veh_req);
    }

    public function get_requests(Request $request) {
        $veh_req = DB::select("SELECT * FROM public.vehicle_request WHERE is_approved = false AND is_denied = false AND CURRENT_DATE <= timeout_date ORDER BY id ASC");
        return json_encode($veh_req);
    }

    public function get_approved(Request $request) {
        $veh_approved = DB::select("SELECT * FROM public.vehicle_request WHERE is_approved = true AND timein_date IS NULL ORDER BY id ASC");
        return json_encode($veh_approved); 
    }

    public function get_denied(Request $request) {
        $veh_denied = DB::select("SELECT * FROM public.vehicle_request WHERE is_denied = true AND CURRENT_DATE <= timeout_date ORDER BY id ASC");
        return json_encode($veh_denied); 
    }

    public function approve_vehicle(Request $request) {
        if (strlen($request->vehicle) == 0) {
            return json_encode('Vehicle field required.');
        }

        $id = $request->id;

        $updates = [
            'is_approved'       => true,
            'approved_vehicle'  => $request->vehicle
        ];

        $sup = DB::select("select supervisor from vehicle_request where id = $id");

        $supervisor = $sup[0]->supervisor;
  
        $email = DB::select("
            SELECT supervisor_email
            FROM vehicle_request_supervisors 
            WHERE supervisor ILIKE '$supervisor'
        ");

        $sup_email = $email[0]->supervisor_email;

        $destination = DB::select("SELECT destination FROM vehicle_request WHERE id = $id");
        $purpose = DB::select("SELECT purpose FROM vehicle_request WHERE id = $id");
        $driver = DB::select("SELECT driver FROM vehicle_request WHERE id = $id");

        // dd($destination);
        
        if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
            $data = [
                'vehicle'     => $request->vehicle,
                'destination' => $destination[0]->destination,
                'purpose'     => $purpose[0]->purpose,
                'driver'      => $driver[0]->driver,
                'supervisor'  => $supervisor
            ];

            Mail::send('mail.email-approve', $data, function($message) use($sup_email) {
                $message->to($sup_email, 'Response')->subject("Vehicle Request Response");
                $message->from('henrywiebe@westracbelize.com', 'Westrac Ltd Vehicle Request');
            });

            return json_encode('y');
        } else {
            return json_encode('n');
        }
    }

    public function deny_vehicle(Request $request) {
        if (strlen($request->deny_reason) == 0) {
            return json_encode('All fields required.');
        }

        $id = $request->id;

        $updates = [
            'is_denied'    => true,
            'deny_reason'  => $request->deny_reason
        ];

        $sup = DB::select("select supervisor from vehicle_request where id = $id");

        $supervisor = $sup[0]->supervisor;
  
        $email = DB::select("
            SELECT supervisor_email
            FROM vehicle_request_supervisors 
            WHERE supervisor ILIKE '$supervisor'
        ");

        $sup_email = $email[0]->supervisor_email;
        
        if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
            $data = ['reason' => $request->deny_reason];

            Mail::send('mail.email-deny', $data, function($message) use($sup_email) {
                $message->to($sup_email, 'Response')->subject("Vehicle Request Response");
                $message->from('henrywiebe@westracbelize.com', 'Westrac Ltd Vehicle Request');
            });

            return json_encode('y');
        } else {
            return json_encode('n');
        }
    }

    public function get_all_requests(Request $request) {
        if ($request->from_date > $request->to_date) {
            return json_encode('e');
        }

        $args = [
            'from_date' => date('Y-m-d H:i:s', strtotime ($request->from_date)),
            'to_date'   => date('Y-m-d H:i:s', strtotime ($request->to_date . '+1 day')),
        ]; 

        $veh_req = DB::select("SELECT * FROM public.vehicle_request WHERE created_on BETWEEN :from_date AND :to_date ORDER BY id DESC", $args);
        return ($veh_req) ? json_encode($veh_req) : json_encode('n');
    }

    public function edit_approved(Request $request) {
        if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) == 0) {
            return json_encode('e');
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            $updates = [
                'driver'  => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            $time_out = DB::select("SELECT timeout_date FROM vehicle_request WHERE id = $id");

            $out_date = $time_out[0]->timeout_date;
            
            if($out_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'  => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            $updates = [
                'timeout_date'  => $request->timeout_date
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            $updates = [
                'approved_vehicle'  => $request->approved_vehicle
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            if($request->timeout_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'  => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
                'timeout_date' => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            $time_out = DB::select("SELECT timeout_date FROM vehicle_request WHERE id = $id");

            $out_date = $time_out[0]->timeout_date;
            
            if($out_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'      => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
                'approved_vehicle' => $request->approved_vehicle,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            $updates = [
                'timeout_date'     => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
                'approved_vehicle' => $request->approved_vehicle,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            $time_out = DB::select("SELECT timeout_date FROM vehicle_request WHERE id = $id");

            $out_date = $time_out[0]->timeout_date;
            
            if($out_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'   => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
                'driver'        => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            $updates = [
                'timeout_date'   => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
                'driver'         => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            $updates = [
                'approved_vehicle' => $request->approved_vehicle,
                'driver'           => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) == 0) {
            $id = $request->id;

            if($request->timeout_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'      => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
                'timeout_date'     => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
                'approved_vehicle' => $request->approved_vehicle,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) == 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            if($request->timeout_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'      => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
                'timeout_date'     => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
                'driver'           => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) != 0 && strlen($request->timeout_date) == 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            $time_out = DB::select("SELECT timeout_date FROM vehicle_request WHERE id = $id");

            $out_date = $time_out[0]->timeout_date;
            
            if($out_date > $request->timein_date) {
                return json_encode('i');
            }

            $updates = [
                'timein_date'      => date('Y-m-d H:i:s', strtotime ($request->timein_date)),
                'approved_vehicle' => $request->approved_vehicle,
                'driver'           => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else if (strlen($request->timein_date) == 0 && strlen($request->timeout_date) != 0 && strlen($request->approved_vehicle) != 0 && strlen($request->driver) != 0) {
            $id = $request->id;

            $updates = [
                'timeout_date'     => date('Y-m-d H:i:s', strtotime ($request->timeout_date)),
                'approved_vehicle' => $request->approved_vehicle,
                'driver'           => $request->driver,
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }

        else {
            $id = $request->id;

            if($request->timeout_date > $request->timein_date) {
                return json_encode('i');
            }
    
            $updates = [
                'driver'            => $request->driver,
                'timein_date'       => $request->timein_date,
                'timeout_date'      => $request->timeout_date,
                'approved_vehicle'  => $request->approved_vehicle
            ];
            
            if (DB::table('public.vehicle_request')->where ('id', $id)->update($updates)) {
                return json_encode('y');
            } else {
                return json_encode('n');
            }
        }
    }
}
