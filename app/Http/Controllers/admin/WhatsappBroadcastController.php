<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappTemplate;
use App\Models\Driver;
use App\Models\Customer;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Validator;

class WhatsappBroadcastController extends Controller
{
    public function index()
    {
        $templates = WhatsappTemplate::where('meta_status', 'APPROVED')
            ->orWhere(function($q) {
                $q->where('status', 1)->whereNull('meta_status');
            })->get();
        return view('admin.whatsapp-broadcast.index', compact('templates'));
    }

    public function searchTarget(Request $request)
    {
        $type = $request->get('type'); // 'customers' or 'drivers'
        $search = $request->get('q');

        $data = [];
        if ($type === 'customers') {
            $query = Customer::select('id', 'name', 'phone', 'phone_code');
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }
            $results = $query->limit(50)->get();
            foreach($results as $r) {
                $fullPhone = $r->phone;
                if ($r->phone_code && !str_starts_with($fullPhone, $r->phone_code)) {
                    $fullPhone = $r->phone_code . ltrim($r->phone, '0');
                }
                $data[] = ['id' => $fullPhone, 'text' => $r->name . ' (' . $fullPhone . ')'];
            }
        } elseif ($type === 'drivers') {
            $query = Driver::select('id', 'name', 'phone', 'phone_code');
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }
            $results = $query->limit(50)->get();
            foreach($results as $r) {
                // Determine full phone
                $fullPhone = $r->phone;
                if ($r->phone_code && !str_starts_with($fullPhone, $r->phone_code)) {
                    $fullPhone = $r->phone_code . ltrim($r->phone, '0');
                }
                $data[] = ['id' => $fullPhone, 'text' => $r->name . ' (' . $fullPhone . ')'];
            }
        }

        return response()->json($data);
    }

    public function getTemplate($id)
    {
        $template = WhatsappTemplate::findOrFail($id);
        return response()->json($template);
    }

    public function send(Request $request, WhatsAppServiceInterface $waService)
    {
        $validator = Validator::make($request->all(), [
            'target_type' => 'required|in:customers,drivers,custom',
            'template_id' => 'required|exists:whatsapp_templates,id',
            'variables' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => $validator->errors()->first()]);
        }

        $template = WhatsappTemplate::findOrFail($request->template_id);
        if (!$template->purpose) {
            return response()->json(['status' => 0, 'error' => __('This template does not have a purpose defined. Please edit it in WhatsApp Templates first.')]);
        }

        $phones = [];
        if ($request->target_type === 'custom') {
            if (!$request->custom_numbers) {
                return response()->json(['status' => 0, 'error' => __('Please enter at least one custom number.')]);
            }
            // Explode by comma or newline
            $rawPhones = preg_split('/[\s,]+/', $request->custom_numbers);
            foreach ($rawPhones as $p) {
                $clean = trim($p);
                if (!empty($clean)) {
                    $phones[] = $clean;
                }
            }
        } else {
            // customers or drivers array of phones
            if (empty($request->target_ids)) {
                return response()->json(['status' => 0, 'error' => __('Please select at least one recipient.')]);
            }
            $phones = $request->target_ids; // we set the id to phone number in select2
        }

        if (empty($phones)) {
            return response()->json(['status' => 0, 'error' => __('No valid phone numbers found.')]);
        }

        // Variables array
        $variables = [];
        if ($request->has('variables')) {
            // Sort by key to ensure order 1, 2, 3
            $vars = $request->variables;
            ksort($vars);
            foreach ($vars as $v) {
                $variables[] = $v;
            }
        }

        $successCount = 0;
        foreach ($phones as $phone) {
            // Clean phone (remove spaces, plus, etc.)
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (empty($phone)) continue;
            
            // Queue the message
            $waService->sendTemplateMessage($phone, $template->purpose, $variables, $template->language);
            $successCount++;
        }

        return response()->json(['status' => 1, 'success' => __("Messages queued successfully to :count recipients.", ['count' => $successCount])]);
    }
}
