<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    /**
     * Admin: Összes dokumentum listázása
     */
    public function index()
    {
        $documents = Document::with('user:id,firstName,lastName,email')->latest()->get();
        
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
        
        return response()->json([
            'documents' => $documents
        ]);
    }

    /**
     * Dokumentum feltöltése
     */
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            'type' => 'required|in:discount,diabetes'
        ]);

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('documents', $fileName, 'public');

        $document = Document::create([
            'user_id' => Auth::id(),
            'originalName' => $originalName,
            'fileName' => $fileName, 
            'filePath' => $path, 
            'mimeType' => $file->getMimeType(),
            'fileSize' => $file->getSize(),
            'type' => $request->type,
        ]);

        // Betöltjük a user kapcsolatot is a válaszhoz
        $document->load('user');

        return response()->json([
            'message' => 'Fájl feltöltve',
            'document' => $document
        ], 201);
    }

    /**
     * Felhasználó saját dokumentumának letöltése
     */
    public function download(Document $document)
    {
        // Ellenőrizzük, hogy a felhasználó a saját dokumentumát tölti-e le
        if (Auth::id() !== $document->user_id) {
            return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
        }

        if (!Storage::disk('public')->exists($document->filePath)) { // JAVÍTVA: filePath
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->filePath, $document->originalName); // JAVÍTVA: filePath, originalName
    }

    /**
     * Admin: Bármilyen dokumentum letöltése
     */
    public function adminDownload(Document $document)
    {
        if (!Storage::disk('public')->exists($document->filePath)) { // JAVÍTVA: filePath
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->filePath, $document->originalName); // JAVÍTVA: filePath, originalName
    }

    /**
     * Felhasználó saját dokumentumának törlése
     */
    public function destroy(Document $document)
    {
        if (Auth::id() !== $document->user_id) {
            return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
        }

        if (Storage::disk('public')->exists($document->filePath)) { // JAVÍTVA: filePath
            Storage::disk('public')->delete($document->filePath);
        }

        $document->delete();

        return response()->json(['message' => 'Dokumentum sikeresen törölve']);
    }

    /**
     * Admin: Bármilyen dokumentum törlése
     */
    public function adminDestroy(Document $document)
    {
        if (Storage::disk('public')->exists($document->filePath)) { // JAVÍTVA: filePath
            Storage::disk('public')->delete($document->filePath);
        }

        $document->delete();

        return response()->json(['message' => 'Dokumentum sikeresen törölve']);
    }

    /**
     * Felhasználó dokumentumainak lekérése típus szerint
     */
    public function getByType($type)
    {
        $documents = Auth::user()->documents()
            ->where('type', $type)
            ->latest()
            ->get();

        return response()->json([
            'documents' => $documents
        ]);
    }
}