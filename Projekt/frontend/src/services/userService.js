import api from './api.js'

export const userService = {
    async getAll() {
        try {
            // Mock adatok - később cseréld Laravel API-ra
            const mockUsers = [
                {
                    id: 1,
                    firstName: 'Admin',
                    lastName: 'Eszköz',
                    email: 'admin@iskola.hu',
                    userType: 'Adminisztrátor',
                    status: 'active',
                    hasDiscount: false,
                    city: { id: 1, cityName: 'Budapest' },
                    student_class: { id: 1, className: '9.A' },
                    group: { id: 1, name: 'Matematika csoport' },
                    rfid_card: { id: 1, cardNumber: 'RFID000001' }
                },
                {
                    id: 2,
                    firstName: 'Bence',
                    lastName: 'Kovács',
                    email: 'bence.kovacs@iskola.hu',
                    userType: 'Tanuló',
                    status: 'active',
                    hasDiscount: true,
                    city: { id: 2, cityName: 'Érd' },
                    student_class: { id: 1, className: '9.A' },
                    group: { id: 1, name: 'Matematika csoport' },
                    rfid_card: { id: 2, cardNumber: 'RFID000002' }
                },
                {
                    id: 3,
                    firstName: 'Anna',
                    lastName: 'Nagy',
                    email: 'anna.nagy@iskola.hu',
                    userType: 'Tanuló',
                    status: 'active',
                    hasDiscount: true,
                    city: { id: 3, cityName: 'Székesfehérvár' },
                    student_class: { id: 2, className: '9.B' },
                    group: { id: 2, name: 'Informatika csoport' },
                    rfid_card: { id: 3, cardNumber: 'RFID000003' }
                }
            ]
            
            return { data: mockUsers }
        } catch (error) {
            throw error.response?.data || error
        }
    },

    async get(id) {
        try {
            // Mock user
            const mockUser = {
                id: id,
                firstName: 'Teszt',
                lastName: 'Felhasználó',
                email: `user${id}@iskola.hu`,
                userType: 'Tanuló',
                status: 'active',
                hasDiscount: true
            }
            return { data: mockUser }
        } catch (error) {
            throw error.response?.data || error
        }
    },

    async create(userData) {
        try {
            // Mock create - csak visszaadjuk az adatokat
            console.log('Új felhasználó:', userData)
            return { 
                data: {
                    ...userData,
                    id: Math.floor(Math.random() * 1000) + 4 // Új ID
                }
            }
        } catch (error) {
            throw error.response?.data || error
        }
    },

    async update(id, userData) {
        try {
            // Mock update
            console.log('Felhasználó frissítése:', id, userData)
            return { data: { id, ...userData } }
        } catch (error) {
            throw error.response?.data || error
        }
    },

    async delete(id) {
        try {
            // Mock delete
            console.log('Felhasználó törlése:', id)
            return { message: 'Felhasználó sikeresen törölve' }
        } catch (error) {
            throw error.response?.data || error
        }
    }
}


/*import api from './api.js';

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
};*/