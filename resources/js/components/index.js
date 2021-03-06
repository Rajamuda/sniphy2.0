import Vue from 'vue'
import Card from './Card'
import Child from './Child'
import Button from './Button'
import Checkbox from './Checkbox'
import Uploader from './Uploader'
import Footer from './Footer'
import { HasError, AlertError, AlertSuccess } from 'vform'

// Components that are registered globaly.
[
  Card,
  Child,
  Button,
  Checkbox,
  Uploader,
  HasError,
  AlertError,
  AlertSuccess,
  Footer
].forEach(Component => {
  Vue.component(Component.name, Component)
})
