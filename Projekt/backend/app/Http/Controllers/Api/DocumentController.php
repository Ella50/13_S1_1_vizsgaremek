<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
     * Admin: Összes dokumentum listázása
     */
    public function index()
    {
        $documents = Document::with('user:id,firstName,lastName,email')->latest()->get();
        
        // Átalakítás angol típusokra a frontendnek
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
     * Felhasználó saját dokumentumainak listázása
     */
    public function myDocuments()
    {
        $documents = Auth::user()->documents()->latest()->get();
        
        foreach ($documents as $document) {
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            // Átalakítás angol típusra a frontendnek
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
                'documentType' => 'required|in:discount,diabetes' // Angol értékeket várunk
            ]);

            // Átalakítás magyar értékre az adatbázisba
            $dbType = self::TYPE_MAPPING[$request->documentType];

            \Log::info('Document upload started', [
                'user_id' => Auth::id(),
                'documentType' => $request->documentType,
                'dbType' => $dbType,
                'file_exists' => $request->hasFile('document')
            ]);

            $file = $request->file('document');
            
            // Ellenőrizzük, hogy van-e már ilyen típusú dokumentum
            $existingDocument = Document::where('user_id', Auth::id())
                ->where('documentType', $dbType)
                ->first();
            
            // Ha van régi dokumentum, töröljük
            if ($existingDocument) {
                \Log::info('Deleting existing document', [
                    'document_id' => $existingDocument->id,
                    'type' => $existingDocument->documentType
                ]);
                
                if (Storage::disk('public')->exists($existingDocument->filePath)) {
                    Storage::disk('public')->delete($existingDocument->filePath);
                }
                
                $existingDocument->delete();
            }
            
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
            
            \Log::info('File stored', ['path' => $path]);

            // Dokumentum létrehozása az adatbázisban
            $document = Document::create([
                'user_id' => Auth::id(),
                'originalName' => $cleanOriginalName,
                'fileName' => $fileName,
                'filePath' => $path,
                'mimeType' => $file->getMimeType(),
                'fileSize' => $file->getSize(),
                'documentType' => $dbType, // Magyar érték az adatbázisban
            ]);

            \Log::info('Document created in DB', ['document_id' => $document->id]);

            // Betöltjük a user kapcsolatot is a válaszhoz
            $document->load('user');
            
            // Formázott méret és angol típus hozzáadása
            $document->formatted_size = $document->formattedSize;
            $document->original_name = $document->originalName;
            $document->type = $request->documentType; // Visszaadjuk az angol típust

            return response()->json([
                'message' => 'Fájl feltöltve',
                'document' => $document
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Upload error: ' . $e->getMessage());
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
     * Felhasználó saját dokumentumának törlése
     */
    public function destroy(Document $document)
    {
        try {
            if (Auth::id() !== $document->user_id) {
                return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
            }

            if (Storage::disk('public')->exists($document->filePath)) {
                Storage::disk('public')->delete($document->filePath);
            }

            $document->delete();

            return response()->json(['message' => 'Dokumentum sikeresen törölve']);
            
        } catch (\Exception $e) {
            \Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt a törlés során'], 500);
        }
    }

    /**
     * Admin: Bármilyen dokumentum törlése
     */
    public function adminDestroy(Document $document)
    {
        try {
            if (Storage::disk('public')->exists($document->filePath)) {
                Storage::disk('public')->delete($document->filePath);
            }

            $document->delete();

            return response()->json(['message' => 'Dokumentum sikeresen törölve']);
            
        } catch (\Exception $e) {
            \Log::error('Error admin deleting document: ' . $e->getMessage());
            return response()->json(['error' => 'Hiba történt a törlés során'], 500);
        }
    }

    /**
     * Felhasználó dokumentumainak lekérése típus szerint
     */
    public function getByType($type)
    {
        $dbType = self::TYPE_MAPPING[$type] ?? $type;
        
        $documents = Auth::user()->documents()
            ->where('documentType', $dbType)
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