<template>
  <div id="app">
    <Navigation v-if="showNavigation" />
    <main :class="{ 'with-nav': showNavigation }">
      <router-view />
    </main>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navigation from './components/Navigation.vue'

export default {
  components: { Navigation },
  
  setup() {
    const route = useRoute()
    
    const showNavigation = computed(() => {
      return !['/login', '/register'].includes(route.path)
    })
    
    return { showNavigation }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;

}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen,
    Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  background: hsl(0, 0%, 96%);
}

main {
  min-height: calc(100vh - 64px);
}

main.with-nav {
  padding-top: 0;
}
</style>