setext-style headers (processed first by the parser)

(1) Headers with auto-ID:

Header 1
========

Header 2
--------

(2) Headers with custom ID attributes:

Header 1 {#my-custom-id-attribute}
========

Header 2 {#my-another-custom-id-attribute}
--------

atx-style headers (processed after the setext headers)

(1) Headers with auto-ID:

# Header 1

## Header 2

### Header 3

#### Header 4

##### Header 5

###### Header 6

(2) Repeat the same headings to ensure that the ID are unique:

# Header 1

## Header 2

### Header 3

#### Header 4

##### Header 5

###### Header 6

(3) Repeat again the same headings, but with the complete atx-style syntax:

# Header 1 #

## Header 2 ##

### Header 3 ###

#### Header 4 ####

##### Header 5 #####

###### Header 6 ######

(4) Headers with custom ID attributes:

# Header 1 {#my-custom-id-1}

## Header 2 {#my-custom-id-2}

### Header 3 {#my-custom-id-3}

#### Header 4 {#my-custom-id-4}

##### Header 5 {#my-custom-id-5}

###### Header 6 {#my-custom-id-6}

(5) Headers with custom ID attributes and using the complete atx-style syntax:

# Header 1 # {#my-other-custom-id-1}

## Header 2 ## {#my-other-custom-id-2}

### Header 3 ### {#my-other-custom-id-3}

#### Header 4 #### {#my-other-custom-id-4}

##### Header 5 ##### {#my-other-custom-id-5}

###### Header 6 ###### {#my-other-custom-id-6}
