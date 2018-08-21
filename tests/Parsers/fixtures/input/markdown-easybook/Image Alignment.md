// regular image not aligned
![Test image](figure1.png)

// "alt text" has a whitespace on the left -> the image is left aligned
![ Test image](figure1.png)

// "alt text" has a whitespace on the right -> the image is right aligned
![Test image ](figure1.png)

// "alt text" has whitespaces both on the left and on the right -> the image is centered
![ Test image ](figure1.png)

If you enclose alt text with quotes, make sure that whitespaces are placed
outside the quotes. The following images for example don't define any alignment:

!["Test image"](figure1.png)

![" Test image"](figure1.png)

!["Test image "](figure1.png)

![" Test image "](figure1.png)

Image alignment is also possible when using the alternative image syntax:

![Test image][1]

![ Test image][1]

![Test image ][1]

![ Test image ][1]

[1]: figure1.png