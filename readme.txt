Order_total module: Priority Handling v.1.2.1
License:       GPL v2.0 
Author: Markus Lankeit (markus@lankeit.org)

Based on "Shipping Insurance" module:
Original Author:  Günter Sammet (Gunter@SammySolutions.com)
Rework Author:  Richard Bartz (richard@bartz.net)
Rework Author:  Byron Herrera (bh@silencesoft.com)
Rework Author: Jade True (jade@sagefish.com)

This module is a modification of the Insurance Shipping module, which is a modification of the 
low order fee module from Harald Ponce de Leon.


Introduction
------------
If you offer your customer "rush processing" for a service fee, then this module is for you.
This module allows the customer to specify "priority handling," and allows you to add an 
appropriate service charge.  When enabled, the customer sees a "Priority Handling" box on 
step 2 of the checkout process.  If the customer decides they want rush processing, they 
check the box.  Step 3 will show the priority handling charge between sub-total and tax.

You can specify the handling charge either based on a percentage of the cart total or 
define charge tiers based on ranges.  To setup a flat-fee charge, use the charge tiers and 
make the tier range very large.

Install
-------
1.) Copy the files into the same directory structure provided in the archive.

2.) Activate in admin (Modules->Order Total Modules) and edit the following settings.

	a. Enable Priority Handling Module?  True 

	b. Offer Priority Handling? True (both a. & b. need to be False to disable module)

	c. Sort Order?  
		Set this to the order you want it to appear in the totals table. Default is 150. 
		Be careful NOT to have two modules with the same order number. 

	d. Priority Handling Charge Type
		Choose whether to calculate by percent or define a tier structure

	e. Handling Charge: Percentage  
		Enter the percentage to use for handling charge.   

	f. Handling Charge: Fee Tier
		Enter the fee tier to use.  

	g. Handling Charge: Price Tier
		Enter the price tier to use for defining price ranges.
		Note: for tiered handling, the charge is [(order sub-total) / (price tier)] * (fee tier)
		Note: to setup a flat-fee scheme, make the price tier large and enter your flat fee in the fee tier.

	h. Handling Charge: Price Tier Ceiling
		Enter the maximum amount to be used for tiered structures.
		Priority charges will not be assessed for any amounts above this maximum.
		The detfault tier values setup a 50 cent charge for every $100 tier up to a $1000 maximum,
		so the maximum priority charge that will be assessed is $5 (10 possible tiers with the default setting).
		If the price tier was $10, then the maximum priority charge would jump to $50 (100 possible tiers).
		
	i. Tax Class... 
		Apply which class, if any, to this type of handling fee.
		Note: services charges may fall under separate categories as goods in some areas.
		      You may need to setup a different tax class for this.

	j. Tax Display  
		Usually, you want to combine taxes on your invoice by class (default).  Otherwise,
		you can choose to add any defined tax into the handling charge.


Upgrade from 1.0
----------------
1.) Login as Admin, go to Modules->Order Total Modules, and record your current settings.

2.) Uninstall the module.

3.) Copy the files into the same directory structure provided in the archive.

3.) Re-install the module from Modules->Order Total Modules

4.) Re-apply the previously recorded settings.



HISTORY:
--------
Release 1.2.1, 130529 mlankeit
Release 1.2, 071312 mlankeit
Release 1.1, 071010 Nick Rodgers
Release 1.0, 061102 mlankeit

Change Log:
-----------
Release 1.2.1:
o Tested against Zen Cart release 1.5.1.  No code changes--just updated this readme file. 

Release 1.2:
o Fixed bug to make module work with Zen Cart release 1.3.8.  Tested backward compatibility with 1.3.7.
o Fixed bug with tier calculation.  It used "total" before, resulting in the priority charge being included in determining tier level.
	Now, tier calculation uses "subtotal" to determine tier level.
o Added Price Tier Ceiling functionality.

Release 1.1:
o Fixed bug that would generate a "1265 Data truncated for column 'value' at row 1 zencart" when going from step 2 to step 3.

Previous work history:
PRERELEASE - First draft to see if this could be implemented into this module class (Gunter)
MYRELEASE 1.0 - March 13, 2003, works for me with snapshot 11 Nov 2002 (Richard)
BETA 2.0 - December 2, 2005, works good for me (Byron)
BETA 2.0b New Release by Jade True, Feb. 28th 2006.

--> PLEASE SEND FEEDBACK (comments/critique/bugs) to Gunter@SammySolutions.com. THANKS!!!
--> PLEASE SEND FEEDBACK (comments/critique/bugs) to richard@bartz.net. THANKS!!!
--> PLEASE SEND FEEDBACK (comments/critique/bugs) to bh@silencesoft.com. THANKS!!!
--> PLEASE SEND FEEDBACK (comments/critique/bugs) to jade@sagefish.com. THANKS!!!
--> PLEASE SEND FEEDBACK (comments/critique/bugs) to markus@lankeit.org. THANKS!!!

