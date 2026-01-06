import axios from 'axios'

const API_URL = 'http://localhost:8000/api'

class AuthService {
  constructor() {
    this.api = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    })

    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token')
        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }
        return config
      },
      (error) => {
        return Promise.reject(error)
      }
    )

    this.api.interceptors.response.use(
      (response) => {
        // BOM eltávolítás
        if (typeof response.data === 'string') {
          response.data = response.data.replace(/^\uFEFF/, '')
          try {
            response.data = JSON.parse(response.data)
          } catch (e) {
            // Ha nem JSON, akkor marad string
          }
        }
        return response
      },
      (error) => {
        console.error('API Error:', {
          status: error.response?.status,
          data: error.response?.data,
          message: error.message
        })
        
        // Fontos: logout API hívásnál NE töröljünk automatikusan
        // és NE redirecteljünk
        if (error.response?.status === 401) {
          const url = error.config?.url || ''
          
          // Csak logout NEM ÉS más URL esetén
          if (!url.includes('/logout')) {
            console.log('Unauthorized, clearing auth data...')
            this.clearAuth()
            // Fontos: Itt NE redirecteljünk automatikusan
            // A komponens fog kezelni
          }
        }
        return Promise.reject(error)
      }
    )
  }

  async login(credentials) {
    try {
      console.log('Login attempt with:', { email: credentials.email })
      const response = await this.api.post('/login', credentials)
      console.log('Login response:', response.data)
      
      if (response.data.token) {
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))
  
        this.api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
      }
      
      return response.data
    } catch (error) {
      console.error('Login error:', error)
      throw error.response?.data || error
    }
  }

  async register(userData) {
    try {
      const response = await this.api.post('/register', userData)
      return response.data
    } catch (error) {
      console.error('Regisztráció error:', error)
      throw error.response?.data || error
    }
  }

  async logout() {
    const token = this.getToken()
    
    if (!token) {
      this.clearAuth()
      return { success: true }
    }
    
    try {
      const response = await this.api.post('/logout')
      this.clearAuth()
      return response.data
    } catch (error) {
      console.error('Logout API error:', {
        status: error.response?.status,
        data: error.response?.data
      })
      
      // Fontos: itt NEM hívjuk meg a clearAuth()-t
      // mert az interceptor már meghívhatta volna
      // Csak ha 401-es hiba volt és logout hívás volt
      if (error.response?.status === 401) {
        console.log('401: token már érvénytelen, csak töröljük lokálisan')
        // clearAuth() már meghívódhatott az interceptorból
        // De ellenőrizzük, hogy még van-e token
        if (this.getToken()) {
          this.clearAuth()
        }
        return { success: true }
      }
      
      // Egyéb hibák esetén is töröljük
      this.clearAuth()
      return { 
        success: false, 
        message: error.response?.data?.message || 'Sikertelen kijelentkezés' 
      }
    }
  }

  clearAuth() {
    // Összes auth adat törlése
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    localStorage.removeItem('userType')
    localStorage.removeItem('userStatus')
    
    delete this.api.defaults.headers.common['Authorization']
  }

  getToken() {
    return localStorage.getItem('auth_token')
  }

  getUser() {
    const user = localStorage.getItem('user')
    return user ? JSON.parse(user) : null
  }

  isAuthenticated() {
    const token = localStorage.getItem('auth_token')
    console.log('AuthService.isAuthenticated():', {
        hasToken: !!token,
        token: token ? token.substring(0, 20) + '...' : null
    })
    return !!token
  }

  // Getters
  getUserRole() {
    const user = this.getUser()
    return user?.userType || null
  }

  isAdmin() {
    return this.getUserRole() === 'Admin'
  }

  isKitchen() {
    return this.getUserRole() === 'Konyha'
  }

  isStudent() {
    return this.getUserRole() === 'Tanuló'
  }

  isTeacher() {
    return this.getUserRole() === 'Tanár'
  }

  isEmployee() {
    return this.getUserRole() === 'Dolgozó'
  }

  isExternal() {
    return this.getUserRole() === 'Külsős'
  }

  canViewMenu() {
    const allowedRoles = ['Tanuló', 'Tanár', 'Dolgozó', 'Külsős']
    return allowedRoles.includes(this.getUserRole())
  }

  getRoleInfo() {
    const role = this.getUserRole()
    return {
      role,
      isAdmin: role === 'Admin',
      isKitchen: role === 'Konyha',
      isStudent: role === 'Tanuló',
      isTeacher: role === 'Tanár',
      isEmployee: role === 'Dolgozó',
      isExternal: role === 'Külsős',
      canViewMenu: ['Tanuló', 'Tanár', 'Dolgozó', 'Külsős'].includes(role),
      displayName: role === 'Tanuló' ? 'Tanuló' : 
                   role === 'Tanár' ? 'Tanár' : role
    }
  }
  
  async getCurrentUser() {
    try {
      const response = await this.api.get('/user')
      return response.data
    } catch (error) {
      // Ne hívjunk logout()-ot itt, mert az ciklust okozhat
      if (error.response?.status === 401) {
        this.clearAuth()
      }
      throw error
    }
  }
  
  async getCities() {
    try {
      const response = await this.api.get('/cities');
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }
}

export default new AuthService()