import api from './api';

export const productService = {
    // Összes termék lekérése
    async getAll() {
        try {
            const response = await api.get('/products');
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    // Egy termék lekérése
    async get(id) {
        try {
            const response = await api.get(`/products/${id}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    // Új termék létrehozása
    async create(productData) {
        try {
            const response = await api.post('/products', productData);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    // Termék frissítése
    async update(id, productData) {
        try {
            const response = await api.put(`/products/${id}`, productData);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    // Termék törlése
    async delete(id) {
        try {
            const response = await api.delete(`/products/${id}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    }
};