<div data-theme="light">
    <h2 class="bb-title">Squadron Events</h2>       
    <input type="submit" value="Add Event" data-featherlight="#ds-group-event-form" data-featherlight-variant="ds-group-event-modal">
</div>

<div id="ds-group-event-form" class="modal" data-theme="light" >

  <article aria-label="Forced light theme example">
    <h4>Light theme</h4>
    <form>

        <fieldset>
            <legend>Event Type</legend>
            <!-- Event Type -->
            <label for="squadron">
                <input type="radio" id="squadron" name="size" value="squadron" checked>
                Squadron
            </label>
            <label for="community">
                <input type="radio" id="community" name="size" value="community">
                Community
            </label>
        </fieldset>

        <fieldset>
            <legend>Event Details</legend>
            <!-- Event Title -->
            <label for="event-title">
                <input type="text" id="event-title" name="event-title" placeholder="Event Title" required>
            </label>
            <!-- Event Description -->
            <label for="event-description">
                <textarea id="event-description" name="event-description" rows="4" cols="50" placeholder="Event Description"></textarea>
            </label>
            <!-- Event Category -->
            <label for="event-category">
                <select id="event-category" name="event-category" required>
                    <option value="" selected>Select a Category</option>
                    <option>…</option>
                </select>
            </label>
            <label for="event-permitted-aircraft">
                <select id="event-permitted-aircraft" name="event-permitted-aircraft" required>
                    <option value="" selected>Select Permitted Aircraft</option>
                    <option>…</option>
                </select>
            </label>
        </fieldset>

        <fieldset class="grid">
            <legend>Event Date/Time</legend>
            <!-- Event Date -->
            <label for="date">
                <input type="date" id="date" name="date">
            </label>
            <!-- Event Time -->
            <label for="time">
                <input type="time" id="time" name="time">
            </label>
        </fieldset>

        <fieldset>
            <legend>Event Image</legend>
            <!-- File browser -->
            <label for="file">File browser
                <input type="file" id="file" name="file">
            </label>
        </fieldset>

        <fieldset>
            <legend>Tickets</legend>
            <!-- Ticket/RSVP Details -->
            <details>
                <summary>Tickets</summary>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque urna diam, tincidunt nec porta sed, auctor id velit. Etiam venenatis nisl ut orci consequat, vitae tempus quam commodo. Nulla non mauris ipsum. Aliquam eu posuere orci. Nulla convallis lectus rutrum quam hendrerit, in facilisis elit sollicitudin. Mauris pulvinar pulvinar mi, dictum tristique elit auctor quis. Maecenas ac ipsum ultrices, porta turpis sit amet, congue turpis.</p>
            </details>
            <details>
                <summary>RSVPs</summary>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque urna diam, tincidunt nec porta sed, auctor id velit. Etiam venenatis nisl ut orci consequat, vitae tempus quam commodo. Nulla non mauris ipsum. Aliquam eu posuere orci. Nulla convallis lectus rutrum quam hendrerit, in facilisis elit sollicitudin. Mauris pulvinar pulvinar mi, dictum tristique elit auctor quis. Maecenas ac ipsum ultrices, porta turpis sit amet, congue turpis.</p>
            </details>
            <details>
                <summary>Settings</summary>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque urna diam, tincidunt nec porta sed, auctor id velit. Etiam venenatis nisl ut orci consequat, vitae tempus quam commodo. Nulla non mauris ipsum. Aliquam eu posuere orci. Nulla convallis lectus rutrum quam hendrerit, in facilisis elit sollicitudin. Mauris pulvinar pulvinar mi, dictum tristique elit auctor quis. Maecenas ac ipsum ultrices, porta turpis sit amet, congue turpis.</p>
            </details>
            <details>
                <summary>Payment Options</summary>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque urna diam, tincidunt nec porta sed, auctor id velit. Etiam venenatis nisl ut orci consequat, vitae tempus quam commodo. Nulla non mauris ipsum. Aliquam eu posuere orci. Nulla convallis lectus rutrum quam hendrerit, in facilisis elit sollicitudin. Mauris pulvinar pulvinar mi, dictum tristique elit auctor quis. Maecenas ac ipsum ultrices, porta turpis sit amet, congue turpis.</p>
            </details>
        </fieldset>

        <fieldset>
            <legend>Terms</legend>
            <!-- Terms Agreement -->
            <details>
                <summary>Terms and Conditions</summary>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque urna diam, tincidunt nec porta sed, auctor id velit. Etiam venenatis nisl ut orci consequat, vitae tempus quam commodo. Nulla non mauris ipsum. Aliquam eu posuere orci. Nulla convallis lectus rutrum quam hendrerit, in facilisis elit sollicitudin. Mauris pulvinar pulvinar mi, dictum tristique elit auctor quis. Maecenas ac ipsum ultrices, porta turpis sit amet, congue turpis.</p>
            </details>
            <label for="terms">
                <input type="checkbox" id="terms" name="terms">
                I agree to the Terms and Conditions
            </label>
        </fieldset>

      <button type="submit" aria-label="Example button" onclick="event.preventDefault()">Submit</button>

    </form>

  </article>

</div>