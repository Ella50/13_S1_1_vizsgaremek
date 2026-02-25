<?php

namespace App\Http\Controllers;

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
        $documents = Document::with('user:id,name,email')->latest()->get();
        
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
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120', // 5MB
            'type' => 'required|in:discount,diabetes' // Dokumentum típusának megadása
        ]);

        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        // Egyedi fájlnév generálása, hogy ne legyen ütközés
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Fájl mentése a storage/app/public/documents mappába
        $path = $file->storeAs('documents', $fileName, 'public');

        $document = Document::create([
            'user_id' => Auth::id(),
            'original_name' => $originalName, // snake_case a migrációnak megfelelően
            'file_name' => $fileName, // snake_case
            'file_path' => $path, // snake_case
            'mime_type' => $file->getMimeType(), // snake_case
            'file_size' => $file->getSize(), // snake_case
            'type' => $request->type, // 'discount' vagy 'diabetes'
        ]);

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

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    /**
     * Admin: Bármilyen dokumentum letöltése
     */
    public function adminDownload(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'A fájl nem található'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    /**
     * Felhasználó saját dokumentumának törlése
     */
    public function destroy(Document $document)
    {
        // Ellenőrizzük, hogy a felhasználó a saját dokumentumát törli-e
        if (Auth::id() !== $document->user_id) {
            return response()->json(['error' => 'Nincs jogosultságod ehhez a dokumentumhoz'], 403);
        }

        // Fájl törlése a storage-ból
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Rekord törlése az adatbázisból
        $document->delete();

        return response()->json(['message' => 'Dokumentum sikeresen törölve']);
    }

    /**
     * Admin: Bármilyen dokumentum törlése
     */
    public function adminDestroy(Document $document)
    {
        // Fájl törlése a storage-ból
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Rekord törlése az adatbázisból
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