<template>
  <nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container">
      <router-link :to="{ name: user ? 'home' : 'welcome' }" class="navbar-brand">
        {{ appName }}
      </router-link>

      <button class="navbar-toggler" type="button" data-toggle="collapse"
        data-target="#navbarToggler" aria-controls="navbarToggler"
        aria-expanded="false" :aria-label="$t('toggle_navigation')"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarToggler">
        <ul class="navbar-nav">
          <locale-dropdown/>
          <!-- <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li> -->
        </ul>

        <ul class="navbar-nav ml-auto">
          <!-- Authenticated -->
          <template v-if="user">
            <li class="nav-item">
              <router-link :to="{ name: 'jobs.list' }" class="nav-link" :class="{'active': jobsIsActive}">
                {{ $t('jobs') }}
              </router-link>
            </li>
            <li class="nav-item">
              <router-link :to="{ name: 'explore' }" class="nav-link" active-class="active">
                {{ $t('explore') }}
              </router-link>
            </li>
            <li class="nav-item">
              <router-link :to="{ name: 'upload.new' }" class="nav-link" active-class="active">
                {{ $t('upload') }}
              </router-link>
            </li>
            <li class="nav-item">
              <router-link :to="{ name: 'about' }" class="nav-link" active-class="active">
                {{ $t('about') }}
              </router-link>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle text-dark"
                href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img :src="user.photo_url" class="rounded-circle profile-photo mr-1">
                {{ user.name }}
              </a>
              <div class="dropdown-menu">
                <router-link :to="{ name: 'settings.profile' }" class="dropdown-item pl-3">
                  <fa icon="cog" fixed-width/>
                  {{ $t('settings') }}
                </router-link>

                <div class="dropdown-divider"></div>
                <a @click.prevent="logout" class="dropdown-item pl-3"  href="#">
                  <fa icon="sign-out-alt" fixed-width/>
                  {{ $t('logout') }}
                </a>
              </div>
            </li>
          </template>
          <!-- Guest -->
          <template v-else>
            <li class="nav-item">
              <router-link :to="{ name: 'login' }" class="nav-link" active-class="active">
                {{ $t('login') }}
              </router-link>
            </li>
            <li class="nav-item">
              <router-link :to="{ name: 'register' }" class="nav-link" active-class="active">
                {{ $t('register') }}
              </router-link>
            </li>
            <li class="nav-item">
              <router-link :to="{ name: 'about' }" class="nav-link" active-class="active">
                {{ $t('about') }}
              </router-link>
            </li>
          </template>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
import { mapGetters } from 'vuex'
import LocaleDropdown from './LocaleDropdown'

export default {
  data: () => ({
    appName: window.config.appName
  }),


  computed: {
    ...mapGetters({
      user: 'auth/user'
    }),

    jobsIsActive: function() {
      let currentPath = this.$route.path;
      if(currentPath.includes("/jobs")){
        return true;
      }
      return false;
      // let listPath = this.$route.matched;
      // let currentPath = this.$route.name;

      // for(let i=0; i<listPath.length; i++){
      //   if(currentPath == listPath[i].name){
      //     return true;
      //   }
      // }
      // return false;
    }
  },

  components: {
    LocaleDropdown
  },

  methods: {
    async logout () {
      // Log out the user.
      await this.$store.dispatch('auth/logout')

      // Redirect to login.
      this.$router.push({ name: 'login' })
    }
  }
}
</script>

<style scoped>
.profile-photo {
  width: 2rem;
  height: 2rem;
  margin: -.375rem 0;
}
.navbar-light .navbar-nav .nav-link.active {
    color: rgba(255, 255, 255, 0.9);
    background-color: #007bff;
    border-radius: 10px;
}
</style>
