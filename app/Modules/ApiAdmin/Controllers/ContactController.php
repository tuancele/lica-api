<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Contact\ContactResource;
use App\Modules\Contact\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('status') && $request->status !== '') $filters['status'] = $request->status;
            if ($request->has('keyword') && $request->keyword !== '') $filters['keyword'] = $request->keyword;
            
            $perPage = (int) $request->get('limit', 20);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;
            
            $query = Contact::query();
            if (isset($filters['status'])) $query->where('status', $filters['status']);
            if (isset($filters['keyword'])) {
                $keyword = $filters['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                      ->orWhere('email', 'like', '%' . $keyword . '%')
                      ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            }
            $query->orderBy('id', 'desc');
            
            $contacts = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => ContactResource::collection($contacts->items()),
                'pagination' => [
                    'current_page' => $contacts->currentPage(),
                    'per_page' => $contacts->perPage(),
                    'total' => $contacts->total(),
                    'last_page' => $contacts->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get contacts list failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get contacts list'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $contact = Contact::find($id);
            if (!$contact) return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
            
            $contact->update(['status' => '1']);
            
            return response()->json(['success' => true, 'data' => new ContactResource($contact->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Get contact details failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get contact details'], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $contact = Contact::find($id);
            if (!$contact) return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
            $contact->delete();
            return response()->json(['success' => true, 'message' => 'Contact deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Delete contact failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete contact'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), ['status' => 'required|in:0,1']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            $contact = Contact::find($id);
            if (!$contact) return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
            $contact->update(['status' => $request->status]);
            return response()->json(['success' => true, 'message' => 'Contact status updated successfully', 'data' => new ContactResource($contact->fresh())], 200);
        } catch (\Exception $e) {
            Log::error('Update contact status failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update contact status'], 500);
        }
    }
}

