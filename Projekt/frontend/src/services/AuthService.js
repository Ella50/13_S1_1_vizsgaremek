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

    // Request interceptor
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

    // Response interceptor - JAVÍTVA
    this.api.interceptors.response.use(
      (response) => {
        // Ha a válasz tartalmaz BOM-ot, töröljük
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
        
        if (error.response?.status === 401) {
          // Ne redirectoljunk, ha logout endpointon vagyunk
          if (!error.config.url.includes('/logout')) {
            console.log('Unauthorized, clearing auth data...')
            this.clearAuth()
            if (window.location.pathname !== '/login') {
              window.location.href = '/login'
            }
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
        // Set default header for future requests
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
      console.log('Registration attempt:', { 
        email: userData.email, 
        userType: userData.userType 
      })
      const response = await this.api.post('/register', userData)
      console.log('Registration response:', response.data)
      
      // Regisztráció után NEM állítunk be tokent, mert a felhasználó inaktív
      // Csak akkor, ha a backend visszaad tokent (ami nem kéne)
      
      return response.data
    } catch (error) {
      console.error('Registration error:', error)
      throw error.response?.data || error
    }
  }

  async logout() {
    const token = this.getToken()
    
    if (!token) {
      console.log('No token, clearing local auth only')
      this.clearAuth()
      return { success: true }
    }
    
    try {
      console.log('Attempting logout...')
      const response = await this.api.post('/logout')
      console.log('Logout successful:', response.data)
      this.clearAuth()
      return response.data
    } catch (error) {
      console.error('Logout API error:', {
        status: error.response?.status,
        data: error.response?.data
      })
      
      // 401 esetén a token már érvénytelen, csak töröljük lokálisan
      if (error.response?.status === 401) {
        console.log('Token expired or invalid, clearing local auth')
        this.clearAuth()
        return { success: true }
      }
      
      // Egyéb hiba esetén is töröljük lokálisan
      this.clearAuth()
      return { 
        success: false, 
        message: error.response?.data?.message || 'Logout failed' 
      }
    }
  }

  clearAuth() {
    console.log('Clearing auth data...')
    
    // Töröljük az összes auth adatot
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    localStorage.removeItem('userType')
    localStorage.removeItem('userStatus')
    
    // Axios default header törlése
    delete this.api.defaults.headers.common['Authorization']
    
    // Ne redirectoljunk automatikusan, hadd kezelje a komponens
    console.log('Auth data cleared')
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
      this.logout()
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