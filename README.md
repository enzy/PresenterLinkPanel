# PresenterLinkPanel

<strong>Author:</strong> Daniel Robenek<br>
<strong>License:</strong> MIT

## Debug bar panel for Nette Framework

This panel generates links to current presenter/template file.
It is required to register handler to make links work (open them in your text editor). Instruction on how to setup this may be found on http://wiki.nette.org/en/howto-editor-link

Registration is prefered in <strong>bootstrap</strong> or <strong>BasePresenter</strong>:

	new \DebugPanel\PresenterLinkPanel($presenter);





