import SwaggerUI from 'swagger-ui'
import { LitElement, unsafeCSS } from 'lit-element'
import retargetEvents from 'react-shadow-dom-retarget-events'
import Styles from '!css-loader!postcss-loader!sass-loader!../css/swagger_ui.scss' // eslint-disable-line

class SwaggerUIElement extends LitElement {
  static get properties () {
    return {
      url: String
    }
  }

  static get styles () {
    return unsafeCSS(Styles.toString())
  }

  firstUpdated () {
    const swaggerUiContainer = document.createElement('div')
    this.shadowRoot.appendChild(swaggerUiContainer)

    SwaggerUI({
      domNode: swaggerUiContainer,
      url: this.url,
      defaultModelsExpandDepth: 0
    })

    retargetEvents(this.shadowRoot)
  }
}

window.customElements.define('swagger-ui', SwaggerUIElement)
