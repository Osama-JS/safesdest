    /**
     * Get task details with history
     */
    public function show($id)
    {
        try {
            $customer = request()->user();

            $task = Task::where('customer_id', $customer->id)
                ->where('id', $id)
                ->with(['driver', 'vehicle_size', 'pickup', 'delivery', 'ad', 'history'])
                ->first();

            if (!$task) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Task not found'
                ], 404);
            }

            // Get task history
            $history = $task->history()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'action_type' => $item->action_type,
                        'description' => $item->description,
                        'status' => $item->action_type,
                        'note' => $item->description,
                        'created_at' => $item->created_at,
                        'timestamp' => $item->created_at,
                    ];
                });

            $taskData = [
                'id' => $task->id,
                'status' => $task->status,
                'closed' => $task->closed,
                'payment_status' => $task->payment_status,
                'payment_method' => $task->payment_method,
                'pricing_method' => $task->pricing_history['pricing_method_id'] ?? null,
                'pickup' => [
                    'lat' => $task->pickup->latitude,
                    'lng' => $task->pickup->longitude,
                    'address' => $task->pickup->address,
                    'contact_name' => $task->pickup->contact_name,
                    'contact_phone' => $task->pickup->contact_phone,
                    'note' => $task->pickup->note,
                    'scheduled_time' => $task->pickup->scheduled_time,
                    'image' => $task->pickup->image ? url('storage/' . $task->pickup->image) : null,
                ],
                'delivery' => [
                    'lat' => $task->delivery->latitude,
                    'lng' => $task->delivery->longitude,
                    'address' => $task->delivery->address,
                    'contact_name' => $task->delivery->contact_name,
                    'contact_phone' => $task->delivery->contact_phone,
                    'note' => $task->delivery->note,
                    'scheduled_time' => $task->delivery->scheduled_time,
                    'image' => $task->delivery->image ? url('storage/' . $task->delivery->image) : null,
                ],
                'price' => $task->total_price,
                'currency' => 'SAR',
                'driver' => $task->driver ? [
                    'name' => $task->driver->name,
                    'phone' => $task->driver->phone,
                    'image' => $task->driver->image ? asset('storage/' . $task->driver->image) : null,
                ] : null,
                'ad' => $task->ad ? [
                    'description' => $task->ad->description,
                    'min' => $task->ad->lowest_price,
                    'max' => $task->ad->highest_price
                ] : null,
                'vehicle' => $task->vehicle_size
                    ? $task->vehicle_size->type->vehicle->name . '-' . $task->vehicle_size->type->name . ' - ' . $task->vehicle_size->name
                    : null,
                'additional_data' => collect($task->customer_visible_additional_data)->map(function ($item) {
                    if (
                        isset($item['type'], $item['value'])
                        && in_array($item['type'], ['image', 'file_expiration_date'])
                        && is_string($item['value'])
                        && !str_starts_with($item['value'], 'http')
                    ) {
                        $item['value'] = url('storage/' . $item['value']);
                    }
                    return $item;
                }),
                'created_at' => $task->created_at,
                'history' => $history,
            ];

            return response()->json([
                'status' => 200,
                'success' => true,
                'data' => $taskData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get task details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
