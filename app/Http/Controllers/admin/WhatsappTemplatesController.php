<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsappTemplatesController extends Controller
{
    public function index()
    {
        $templatesCount = WhatsappTemplate::count();
        $activeCount = WhatsappTemplate::where('status', 1)->count();
        $inactiveCount = WhatsappTemplate::where('status', 0)->count();

        return view('admin.whatsapp-templates.index', compact('templatesCount', 'activeCount', 'inactiveCount'));
    }

    public function getData(Request $request)
    {
        $columns = [
            1 => 'id',
            2 => 'template_name',
            3 => 'purpose',
            4 => 'language',
            5 => 'status',
            6 => 'created_at',
        ];

        $totalData = WhatsappTemplate::count();
        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')] ?? 'id';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $query = WhatsappTemplate::query();

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where('template_name', 'LIKE', "%{$search}%")
                  ->orWhere('purpose', 'LIKE', "%{$search}%");
            $totalFiltered = $query->count();
        }

        $templates = $query->offset($start)
                           ->limit($limit)
                           ->orderBy($order, $dir)
                           ->get();

        $data = [];
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $nestedData['id']            = $template->id;
                $nestedData['template_name'] = $template->template_name;
                $nestedData['purpose']       = $template->purpose;
                $nestedData['language']      = $template->language;
                $nestedData['category']      = $template->category;
                $nestedData['meta_status']   = $template->meta_status;
                $nestedData['body_text']     = $template->body_text;
                $nestedData['components']    = $template->components; // already cast as array
                $nestedData['status']        = $template->status;
                $nestedData['created_at']    = $template->created_at ? $template->created_at->format('Y-m-d H:i') : '-';
                $nestedData['actions']       = '';
                $data[] = $nestedData;
            }
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ]);
    }

    public function edit(WhatsappTemplate $whatsappTemplate)
    {
        return response()->json($whatsappTemplate);
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'purpose' => 'required|string|unique:whatsapp_templates,purpose,' . ($request->id ?? 0),
                'template_name' => 'required|string',
                'language' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['status' => 0, 'error' => $validator->errors()]);
            }

            if ($request->filled('id')) {
                $template = WhatsappTemplate::findOrFail($request->id);
                
                // Allow user to edit purpose and status locally.
                $template->update([
                    'purpose' => $request->purpose,
                    'status' => $request->status ?? 0,
                ]);
            } else {
                return response()->json(['status' => 0, 'error' => __('Templates can only be created via Meta sync.')]);
            }

            return response()->json(['status' => 1, 'success' => __('Saved successfully')]);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $template = WhatsappTemplate::findOrFail($request->id);
            $template->status = $request->status;
            $template->save();

            return response()->json(['status' => 1, 'success' => __('Status changed successfully')]);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    /**
     * Fetch all templates from WhatsApp Cloud API (Meta)
     * and sync them into local DB for purpose mapping.
     */
    public function fetchFromCloud()
    {
        $wabaId   = env('WHATSAPP_CLOUD_WABA_ID');
        $token    = env('WHATSAPP_CLOUD_TOKEN');
        $apiVer   = env('WHATSAPP_CLOUD_API_VERSION', 'v21.0');

        if (!$wabaId || !$token) {
            return response()->json([
                'status'  => 0,
                'message' => __('WhatsApp Cloud API credentials (WABA ID & Token) are not configured in .env'),
            ]);
        }

        $url = "https://graph.facebook.com/{$apiVer}/{$wabaId}/message_templates";

        try {
            $response = Http::withToken($token)->get($url, [
                'fields' => 'name,status,language,components,category',
                'limit'  => 100,
            ]);

            if (!$response->successful()) {
                Log::error('WhatsApp Cloud API templates fetch failed: ' . $response->body());
                return response()->json([
                    'status'  => 0,
                    'message' => __('Failed to fetch templates from WhatsApp Cloud API'),
                    'error'   => $response->json('error.message') ?? $response->body(),
                ]);
            }

            $metaTemplates = $response->json('data') ?? [];

            // Sync into local DB
            $synced = 0;
            foreach ($metaTemplates as $mt) {
                // Extract body text from components
                $bodyText = '';
                foreach ($mt['components'] ?? [] as $component) {
                    if ($component['type'] === 'BODY') {
                        $bodyText = $component['text'] ?? '';
                    }
                }

                WhatsappTemplate::updateOrCreate(
                    ['template_name' => $mt['name']],
                    [
                        'purpose'       => $mt['name'],
                        'language'      => $mt['language'],
                        'status'        => $mt['status'] === 'APPROVED' ? 1 : 0,
                        'category'      => $mt['category'] ?? null,
                        'meta_status'   => $mt['status'] ?? null,
                        'body_text'     => $bodyText,
                        'components'    => json_encode($mt['components'] ?? []),
                    ]
                );
                $synced++;
            }

            return response()->json([
                'status'  => 1,
                'message' => __('Synced :count templates from WhatsApp Cloud API', ['count' => $synced]),
                'count'   => $synced,
            ]);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $template = WhatsappTemplate::findOrFail($request->id);
            $template->delete();

            return response()->json(['status' => 1, 'success' => __('Deleted successfully')]);
        } catch (Exception $ex) {
            return response()->json(['status' => 2, 'error' => $ex->getMessage()]);
        }
    }
}
