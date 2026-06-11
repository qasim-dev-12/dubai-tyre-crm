import store from '~/store'

export default (to, from, next) => {
  if (store.getters['auth/user'].account_role == 1) {
    next()
  } else {
    next({ name: 'home' })
  }
}
