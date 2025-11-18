import api from './api.js';

export const userService = {
    async getAll() {
        try {
            const response = await api.get('/users');
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    async get(id) {
        try {
            const response = await api.get(`/users/${id}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    async create(userData) {
        try {
            const response = await api.post('/users', userData);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    async update(id, userData) {
        try {
            const response = await api.put(`/users/${id}`, userData);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    async delete(id) {
        try {
            const response = await api.delete(`/users/${id}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    async getByType(type) {
        try {
            const response = await api.get(`/users/type/${type}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    },

    async getByStatus(status) {
        try {
            const response = await api.get(`/users/status/${status}`);
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    }
};