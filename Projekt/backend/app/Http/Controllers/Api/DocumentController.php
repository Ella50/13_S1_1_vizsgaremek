<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{

    const TYPE_DISCOUNT = 'Kedvezmény';
    const TYPE_DIABETES = 'Cukorbetegség';
    

    const TYPE_MAPPING = [
        'discount' => self::TYPE_DISCOUNT,
        'diabetes' => self::TYPE_DIABETES
    ];
    

    const TYPE_MAPPING_REVERSE = [
        self::TYPE_DISCOUNT => 'discount',
        self::TYPE_DIABETES => 'diabetes'
    ];


    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:pending,accepted,all'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $request->input('per_page', 25);
        $query = Document::with('user:id,firstName,lastName,email');


        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('user', function($q) use ($searchTerm) {
                $q->where('firstName', 'like', "%{$searchTerm}%")
                ->orWhere('lastName', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $status = $request->input('status', 'all');
        if ($status === 'pending') {
            $query->where('isAccepted', false);
        } elseif ($status === 'accepted') {
            $query->where('isAccepted', true);
        }

        $query->where('isActive', true);

        $documents = $query->latest()->paginate($perPage);

        $documents->getCollection()->transform(function ($document) {
            $document->type = self::TYPE_MAPPING_REVERSE[$document->documentType] ?? $document->documentType;
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            return $document;
        });

        return response()->json([
            'success' => true,
            'data' => $documents->items(),
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage(),
            'per_page' => $documents->perPage(),
            'total' => $documents->total(),
            'from' => $documents->firstItem(),
            'to' => $documents->lastItem(),
        ]);
    }

    public function myDocuments()
    {
        $documents = Document::where('user_id', Auth::id())
            ->where('isActive', true)  // CSAK az aktívakat
            ->latest()
            ->get();
        
        foreach ($documents as $document) {
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            $document->type = self::TYPE_MAPPING_REVERSE[$document->documentType] ?? $document->documentType;
        }
        
        return response()->json([
            'documents' => $documents
        ]);
    }

    /**
     * Dokumentum feltöltése
     */
     public function store(Request $request)
    {
        try {
            $request->validate([
                'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
                'documentType' => 'required|in:discount,diabetes'
            ]);

            $dbType = self::TYPE_MAPPING[$request->documentType];

            $acceptedDocument = Document::where('user_id', Auth::id())
                ->where('documentType', $dbType)
                ->where('isAccepted', true)
                ->exists();

            if ($acceptedDocument) {
                return response()->json([
                    'message' => 'Már van elfogadott dokumentumod, nem tölthetsz fel újat!'
                ], 403);
            }

            Log::info('Document upload started', [
                'user_id' => Auth::id(),
                'documentType' => $request->documentType,
                'dbType' => $dbType
            ]);

            $file = $request->file('document');
            

            Document::where('user_id', Auth::id())
                ->where('documentType', $dbType)
                ->where('isActive', true)
                ->update(['isActive' => false]);
            

            $originalName = $file->getClientOriginalName();
            $cleanOriginalName = $this->sanitizeFilename($originalName);

            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . uniqid() . '.' . $extension;

            $path = $file->storeAs('documents', $fileName, 'public');
            
            if (!$path) {
                throw new \Exception('Nem sikerült a fájl mentése');
            }

            $document = Document::create([
                'user_id' => Auth::id(),
                'originalName' => $cleanOriginalName,
                'fileName' => $fileName,
                'filePath' => $path,
                'mimeType' => $file->getMimeType(),
                'fileSize' => $file->getSize(),
                'documentType' => $dbType,
                'isActive' => true,
                'isAccepted' => false
            ]);

            $document->load('user');
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            $document->type = $request->documentType;
            $document->can_upload = true; // Még nincs elfogadva, lehet feltölteni

            return response()->json([
                'message' => 'Fájl feltöltve, admin jóváhagyásra vár',
                'document' => $document
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Hiba történt a feltöltés során',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function accept(Document $document)
    {
        try {
            Document::where('user_id', $document->user_id)
                ->where('documentType', $document->documentType)
                ->where('id', '!=', $document->id)
                ->update(['isActive' => false]);

            $document->update([
                'isActive' => true,
                'isAccepted' => true
            ]);

            return response()->json([
                'message' => 'Dokumentum elfogadva',
                'document' => $document
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error accepting document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt'], 500);
        }
    }

    public function reject(Document $document)
    {
        try {
            $document->update([
                'isActive' => false,
                'isAccepted' => false
            ]);

            return response()->json([
                'message' => 'Dokumentum elutasítva',
                'document' => $document
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error rejecting document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt'], 500);
        }
    }

     //Fájlnév tisztítása
     
    private function sanitizeFilename($filename)
    {
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ú' => 'U', 'Ü' => 'U', 'Ű' => 'U',
            ' ' => '_',
        ];
        
        $clean = strtr($filename, $unwanted);
        $clean = preg_replace('/[^a-zA-Z0-9._-]/', '', $clean);
        
        return $clean;
    }

    public function download(Document $document)
    {
        if (Auth::id() !== $document->user_id) {
            return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
        }

        if (!Storage::disk('public')->exists($document->filePath)) {
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->filePath, $document->originalName);
    }


    public function adminDownload(Document $document)
    {
        if (!Storage::disk('public')->exists($document->filePath)) {
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->filePath, $document->originalName);
    }

  
     // Felhasználó saját dokumentumának TÖRLÉSE (valójában inaktívvá tétel)
     
    public function destroy(Document $document)
    {
        try {
            if (Auth::id() !== $document->user_id) {
                return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
            }

          
            $document->update(['isActive' => false]);

            return response()->json(['message' => 'Dokumentum eltávolítva a megjelenítésből']);
            
        } catch (\Exception $e) {
            Log::error('Error deactivating document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt'], 500);
        }
    }

    //Admin: VÉGLEGES TÖRLÉS 
     
    public function adminDestroy(Document $document)
    {
        try {
            if (Storage::disk('public')->exists($document->filePath)) {
                Storage::disk('public')->delete($document->filePath);
            }

            $document->delete();

            return response()->json(['message' => 'Dokumentum véglegesen törölve']);
            
        } catch (\Exception $e) {
            Log::error('Error admin deleting document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt a törlés során'], 500);
        }
    }


    public function getByType($type)
    {
        $dbType = self::TYPE_MAPPING[$type] ?? $type;
        
        $documents = Document::where('user_id', Auth::id())
            ->where('documentType', $dbType)
            ->where('isActive', true) 
            ->latest()
            ->get();

        foreach ($documents as $document) {
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            $document->type = $type;
        }

        return response()->json([
            'documents' => $documents
        ]);
    }
}