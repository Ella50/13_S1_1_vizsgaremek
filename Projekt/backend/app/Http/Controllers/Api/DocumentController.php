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
    // Konstansok a típusokhoz
    const TYPE_DISCOUNT = 'Kedvezmény';
    const TYPE_DIABETES = 'Cukorbetegség';
    
    // Angol -> Magyar mapping
    const TYPE_MAPPING = [
        'discount' => self::TYPE_DISCOUNT,
        'diabetes' => self::TYPE_DIABETES
    ];
    
    // Magyar -> Angol mapping (visszafelé)
    const TYPE_MAPPING_REVERSE = [
        self::TYPE_DISCOUNT => 'discount',
        self::TYPE_DIABETES => 'diabetes'
    ];

    /**
     * Admin: Összes dokumentum listázása (minden dokumentum, akár aktív, akár inaktív)
     */
    public function index()
    {
        $documents = Document::with('user:id,firstName,lastName,email')
            ->latest()
            ->get();
        
        foreach ($documents as $document) {
            $document->type = self::TYPE_MAPPING_REVERSE[$document->documentType] ?? $document->documentType;
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
        }
        
        return response()->json([
            'documents' => $documents
        ]);
    }

    /**
     * Felhasználó saját dokumentumainak listázása (CSAK AKTÍV)
     */
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

            Log::info('Document upload started', [
                'user_id' => Auth::id(),
                'documentType' => $request->documentType,
                'dbType' => $dbType,
                'file_exists' => $request->hasFile('document')
            ]);

            $file = $request->file('document');
            
            // Meglévő AKTÍV dokumentum inaktívvá tétele
            Document::where('user_id', Auth::id())
                ->where('documentType', $dbType)
                ->where('isActive', true)
                ->update(['isActive' => false]);
            
            // Eredeti fájlnév tisztítása
            $originalName = $file->getClientOriginalName();
            $cleanOriginalName = $this->sanitizeFilename($originalName);
            
            // Biztonságos fájlnév generálása
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . uniqid() . '.' . $extension;
            
            // Fájl mentése
            $path = $file->storeAs('documents', $fileName, 'public');
            
            if (!$path) {
                throw new \Exception('Nem sikerült a fájl mentése');
            }
            
            Log::info('File stored', ['path' => $path]);

            // Új dokumentum létrehozása (alapból isActive = true)
            $document = Document::create([
                'user_id' => Auth::id(),
                'originalName' => $cleanOriginalName,
                'fileName' => $fileName,
                'filePath' => $path,
                'mimeType' => $file->getMimeType(),
                'fileSize' => $file->getSize(),
                'documentType' => $dbType,
            ]);

            Log::info('Document created in DB', ['document_id' => $document->id]);

            $document->load('user');
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            $document->type = $request->documentType;

            return response()->json([
                'message' => 'Fájl feltöltve',
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

    /**
     * Fájlnév tisztítása
     */
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

    /**
     * Felhasználó saját dokumentumának letöltése
     */
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

    /**
     * Admin: Bármilyen dokumentum letöltése
     */
    public function adminDownload(Document $document)
    {
        if (!Storage::disk('public')->exists($document->filePath)) {
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->filePath, $document->originalName);
    }

    /**
     * Felhasználó saját dokumentumának TÖRLÉSE (valójában inaktívvá tétel)
     */
    public function destroy(Document $document)
    {
        try {
            if (Auth::id() !== $document->user_id) {
                return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
            }

            // NEM TÖRÖLJÜK, csak inaktívvá tesszük
            $document->update(['isActive' => false]);

            return response()->json(['message' => 'Dokumentum eltávolítva a megjelenítésből']);
            
        } catch (\Exception $e) {
            Log::error('Error deactivating document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt'], 500);
        }
    }

    /**
     * Admin: TELJES TÖRLÉS (fizikailag is)
     */
    public function adminDestroy(Document $document)
    {
        try {
            if (Storage::disk('public')->exists($document->filePath)) {
                Storage::disk('public')->delete($document->filePath);
            }

            $document->delete(); // Végleges törlés

            return response()->json(['message' => 'Dokumentum véglegesen törölve']);
            
        } catch (\Exception $e) {
            Log::error('Error admin deleting document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt a törlés során'], 500);
        }
    }

    /**
     * Felhasználó dokumentumainak lekérése típus szerint (CSAK AKTÍV)
     */
    public function getByType($type)
    {
        $dbType = self::TYPE_MAPPING[$type] ?? $type;
        
        $documents = Document::where('user_id', Auth::id())
            ->where('documentType', $dbType)
            ->where('isActive', true)  // CSAK az aktívakat
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