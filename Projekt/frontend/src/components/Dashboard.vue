<template>
    <div class="dashboard">
        <header>
            <h1>Üdvözöljük, {{ user.first_name }}!</h1>
            <button @click="logout">Kijelentkezés</button>
        </header>
        
        <main>
            <div class="user-info">
                <h2>Felhasználói információk</h2>
                <p><strong>Név:</strong> {{ user.last_name }} {{ user.first_name }}</p>
                <p><strong>Email:</strong> {{ user.email }}</p>
                <p><strong>Státusz:</strong> {{ user.status }}</p>
                <p><strong>Felhasználó típus:</strong> {{ user.user_type }}</p>
            </div>
        </main>
    </div>
</template>

<script>
import axios from '../../axios'

export default {
    name: 'Dashboard',
    data() {
        return {
            user: {}
        }
    },
    mounted() {
        this.loadUser()
    },
    methods: {
        async loadUser() {
            try {
                const userData = localStorage.getItem('user')
                if (userData) {
                    this.user = JSON.parse(userData)
                }
            } catch (error) {
                console.error('Hiba a felhasználói adatok betöltésekor:', error)
            }
        },
        async logout() {
            try {
                await axios.post('/logout')
            } catch (error) {
                console.error('Kijelentkezési hiba:', error)
            }
            localStorage.removeItem('auth_token')
            localStorage.removeItem('user')
            this.$router.push('/login')
        }
    }
}
</script>

<style scoped>
.dashboard {
    padding: 2rem;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #ddd;
}

.user-info {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    max-width: 600px;
}

.user-info h2 {
    margin-bottom: 1rem;
}

.user-info p {
    margin-bottom: 0.5rem;
}

button {
    padding: 0.5rem 1rem;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background: #c82333;
}
</style>